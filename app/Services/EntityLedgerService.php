<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Services\Dto\LedgerRow;
use App\Support\DateRange;
use App\Support\Money;
use Illuminate\Support\Collection;

class EntityLedgerService
{
    /**
     * Running Dr/Cr ledger for one funding entity.
     *
     * Running balance is recomputed in PHP so unit filters stay consistent.
     *
     * @param  list<int>|null  $costCenterIds
     * @return Collection<int, LedgerRow>
     */
    public function rows(int $entityId, ?array $costCenterIds = null, ?DateRange $range = null): Collection
    {
        $openingBalance = $this->openingBalance($entityId, $costCenterIds, $range);

        $query = LedgerEntry::query()
            ->with('costCenter')
            ->where('entity_id', $entityId)
            ->when($costCenterIds !== null, fn ($query) => $query->whereIn('cost_center_id', $costCenterIds))
            ->when(
                $range !== null && $range->from && $range->to,
                fn ($query) => $query->whereBetween('txn_date', [$range->from, $range->to]),
            )
            ->orderBy('txn_date')
            ->orderBy('source_table')
            ->orderBy('source_id');

        $balance = $openingBalance;

        return $query->get()->map(function (LedgerEntry $raw) use (&$balance): LedgerRow {
            $signed = Money::of($raw->signed_amount);
            $balance = Money::add($balance, $signed);
            $isCredit = Money::cmp($signed, '0') > 0;

            return new LedgerRow(
                txnDate: $raw->txn_date->toDateString(),
                costCenter: $raw->costCenter,
                particulars: (string) $raw->particulars,
                debit: $isCredit ? Money::zero() : Money::abs($signed),
                credit: $isCredit ? $signed : Money::zero(),
                runningBalance: $balance,
                sourceTable: (string) $raw->source_table,
                sourceId: (int) $raw->source_id,
            );
        });
    }

    /**
     * @param  list<int>|null  $costCenterIds
     */
    public function openingBalance(int $entityId, ?array $costCenterIds = null, ?DateRange $range = null): string
    {
        if ($range === null || $range->from === null) {
            return Money::zero();
        }

        return Money::of(
            LedgerEntry::query()
                ->where('entity_id', $entityId)
                ->when($costCenterIds !== null, fn ($query) => $query->whereIn('cost_center_id', $costCenterIds))
                ->where('txn_date', '<', $range->from)
                ->sum('signed_amount') ?? 0,
        );
    }

    /**
     * @param  list<int>|null  $costCenterIds
     */
    public function closingBalance(int $entityId, ?array $costCenterIds = null, ?DateRange $range = null): string
    {
        $opening = $this->openingBalance($entityId, $costCenterIds, $range);
        $periodNet = $this->rows($entityId, $costCenterIds, $range)->reduce(
            fn (string $carry, LedgerRow $row): string => Money::add(
                $carry,
                Money::sub($row->credit, $row->debit),
            ),
            Money::zero(),
        );

        return Money::add($opening, $periodNet);
    }

    /**
     * Cumulative signed balance for an entity through the given date (inclusive).
     *
     * @param  list<int>|null  $costCenterIds
     */
    public function balanceAsOf(int $entityId, string $asOfDate, ?array $costCenterIds = null): string
    {
        return Money::of(
            LedgerEntry::query()
                ->where('entity_id', $entityId)
                ->when($costCenterIds !== null, fn ($query) => $query->whereIn('cost_center_id', $costCenterIds))
                ->where('txn_date', '<=', $asOfDate)
                ->sum('signed_amount') ?? 0,
        );
    }
}
