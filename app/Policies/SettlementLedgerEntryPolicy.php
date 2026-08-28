<?php

namespace App\Policies;

use App\Models\SettlementLedgerEntry;
use App\Models\User;

class SettlementLedgerEntryPolicy extends AdminPolicy
{
    public function view(User $user, SettlementLedgerEntry $entry): bool
    {
        return true;
    }

    public function update(User $user, SettlementLedgerEntry $entry): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SettlementLedgerEntry $entry): bool
    {
        return $user->isAdmin();
    }
}
