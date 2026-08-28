<?php

namespace App\Policies;

use App\Models\BankingSnapshot;
use App\Models\User;

class BankingSnapshotPolicy extends AdminPolicy
{
    public function view(User $user, BankingSnapshot $bankingSnapshot): bool
    {
        return true;
    }

    public function update(User $user, BankingSnapshot $bankingSnapshot): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, BankingSnapshot $bankingSnapshot): bool
    {
        return $user->isAdmin();
    }
}
