<?php

namespace App\Services;

use App\Enums\EntityType;
use App\Models\BankingSnapshot;
use App\Models\Entity;
use App\Services\Dto\BankingPosition;
use App\Support\Money;

class BankingService
{
    public function __construct(
        private EntityLedgerService $ledgerService,
    ) {}

    public function current(): ?BankingPosition
    {
        return $this->asOf(now()->toDateString());
    }

    public function asOf(string $date): ?BankingPosition
    {
        $snapshot = BankingSnapshot::query()
            ->where('as_of_date', '<=', $date)
            ->orderByDesc('as_of_date')
            ->orderByDesc('id')
            ->first();

        if ($snapshot === null) {
            return null;
        }

        return $this->position($snapshot);
    }

    public function alamUtilisedAsOf(string $date): string
    {
        $alam = Entity::query()
            ->where('entity_type', EntityType::NonShareholderFunder)
            ->where('name', 'Payable to Alam')
            ->first();

        if ($alam === null) {
            return Money::zero();
        }

        return $this->ledgerService->balanceAsOf($alam->id, $date);
    }

    public function position(BankingSnapshot $snapshot): BankingPosition
    {
        $alamUtilised = $this->alamUtilisedAsOf($snapshot->as_of_date->toDateString());

        return new BankingPosition(
            snapshot: $snapshot,
            ccAvailable: Money::sub($snapshot->cc_limit, $snapshot->cc_utilised),
            alamUtilised: $alamUtilised,
            alamPayable: Money::mul($alamUtilised, '-1'),
        );
    }
}
