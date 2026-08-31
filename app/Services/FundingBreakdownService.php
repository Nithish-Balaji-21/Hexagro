<?php

namespace App\Services;

use App\Enums\DebitCategory;
use App\Models\CostCenter;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\Transfer;
use App\Services\Dto\EntityFundingRow;
use App\Support\DateRange;
use App\Support\Money;
use Illuminate\Support\Collection;

class FundingBreakdownService
{
    /**
     * Per-entity funding totals for a cost center.
     *
     * @return Collection<int, EntityFundingRow>
     */
    public function forCostCenter(CostCenter $costCenter, ?DateRange $range = null): Collection
    {
        $entities = Entity::query()->active()->orderBy('id')->get();

        $debitQuery = DebitTransaction::query()
            ->select('paid_through_entity_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN category = ? THEN amount ELSE 0 END), 0) as expenses', [DebitCategory::Expense->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN category = ? THEN amount ELSE 0 END), 0) as raw_materials', [DebitCategory::RawMaterials->value])
            ->where('cost_center_id', $costCenter->id);

        if ($range !== null && $range->from && $range->to) {
            $debitQuery->whereBetween('txn_date', [$range->from, $range->to]);
        }

        $debits = $debitQuery
            ->groupBy('paid_through_entity_id')
            ->get()
            ->keyBy('paid_through_entity_id');

        $creditQuery = CreditTransaction::query()
            ->select('received_to_entity_id')
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->where('cost_center_id', $costCenter->id);

        if ($range !== null && $range->from && $range->to) {
            $creditQuery->whereBetween('txn_date', [$range->from, $range->to]);
        }

        $credits = $creditQuery
            ->groupBy('received_to_entity_id')
            ->get()
            ->keyBy('received_to_entity_id');

        $transferInQuery = Transfer::query()
            ->select('to_entity_id')
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->where('cost_center_id', $costCenter->id);

        if ($range !== null && $range->from && $range->to) {
            $transferInQuery->whereBetween('txn_date', [$range->from, $range->to]);
        }

        $transfersIn = $transferInQuery
            ->groupBy('to_entity_id')
            ->get()
            ->keyBy('to_entity_id');

        $transferOutQuery = Transfer::query()
            ->select('from_entity_id')
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->where('cost_center_id', $costCenter->id);

        if ($range !== null && $range->from && $range->to) {
            $transferOutQuery->whereBetween('txn_date', [$range->from, $range->to]);
        }

        $transfersOut = $transferOutQuery
            ->groupBy('from_entity_id')
            ->get()
            ->keyBy('from_entity_id');

        return $entities->map(function (Entity $entity) use ($debits, $credits, $transfersIn, $transfersOut): EntityFundingRow {
            $debitRow = $debits->get($entity->id);
            $expenses = Money::of($debitRow->expenses ?? 0);
            $rawMaterials = Money::of($debitRow->raw_materials ?? 0);
            $otherDebits = Money::of($transfersIn->get($entity->id)?->total ?? 0);
            $creditTotal = Money::add(
                $credits->get($entity->id)?->total ?? 0,
                $transfersOut->get($entity->id)?->total ?? 0,
            );
            $entityTotal = Money::sub(
                Money::add(Money::add($expenses, $rawMaterials), $otherDebits),
                $creditTotal,
            );

            return new EntityFundingRow(
                entity: $entity,
                expenses: $expenses,
                rawMaterials: $rawMaterials,
                otherDebits: $otherDebits,
                credits: $creditTotal,
                entityTotal: $entityTotal,
            );
        });
    }

    /**
     * @param  Collection<int, EntityFundingRow>  $rows
     */
    public function rowFor(Collection $rows, Entity $entity): ?EntityFundingRow
    {
        return $rows->first(fn (EntityFundingRow $row): bool => $row->entity->is($entity));
    }
}
