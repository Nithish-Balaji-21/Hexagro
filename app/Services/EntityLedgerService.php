<?php

namespace App\Services;

use App\Models\Views\EntityLedgerRaw;
use App\Services\Dto\LedgerRow;
use App\Support\Money;
use Illuminate\Support\Collection;

class EntityLedgerService
{
    /**
     * Running Dr/Cr ledger for one funding entity.
     *
     * Running balance is recomputed in PHP so unit filters stay consistent.
     * The SQL view `v_entity_ledger` partitions by entity across all units.
     *
     * @param  list<int>|null  $costCenterIds
     * @return Collection<int, LedgerRow>
     */
    public function rows(int $entityId, ?array $costCenterIds = null): Collection
    {
        $rawRows = EntityLedgerRaw::query()
            ->with('costCenter')
            ->where('entity_id', $entityId)
            ->when($costCenterIds !== null, fn ($query) => $query->whereIn('cost_center_id', $costCenterIds))
            ->orderBy('txn_date')
            ->orderBy('source_table')
            ->orderBy('source_id')
            ->get();

        $balance = Money::zero();

        return $rawRows->map(function (EntityLedgerRaw $raw) use (&$balance): LedgerRow {
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
    public function closingBalance(int $entityId, ?array $costCenterIds = null): string
    {
        $last = $this->rows($entityId, $costCenterIds)->last();

        return $last?->runningBalance ?? Money::zero();
    }
}
