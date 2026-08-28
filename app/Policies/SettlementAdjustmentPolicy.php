<?php

namespace App\Policies;

use App\Models\SettlementAdjustment;
use App\Models\User;

class SettlementAdjustmentPolicy extends AdminPolicy
{
    public function view(User $user, SettlementAdjustment $settlementAdjustment): bool
    {
        return true;
    }

    public function update(User $user, SettlementAdjustment $settlementAdjustment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SettlementAdjustment $settlementAdjustment): bool
    {
        return $user->isAdmin();
    }
}
