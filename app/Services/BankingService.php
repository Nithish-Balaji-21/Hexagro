<?php

namespace App\Services;

use App\Models\BankingSnapshot;
use App\Services\Dto\BankingPosition;
use App\Support\Money;

class BankingService
{
    public function current(): ?BankingPosition
    {
        $snapshot = BankingSnapshot::query()
            ->orderByDesc('as_of_date')
            ->orderByDesc('id')
            ->first();

        if ($snapshot === null) {
            return null;
        }

        return $this->position($snapshot);
    }

    public function position(BankingSnapshot $snapshot): BankingPosition
    {
        return new BankingPosition(
            snapshot: $snapshot,
            ccAvailable: Money::sub($snapshot->cc_limit, $snapshot->cc_utilised),
            alamPayable: Money::mul($snapshot->alam_utilised, '-1'),
        );
    }
}
