<?php

namespace App\Policies;

use App\Models\DebitTransaction;
use App\Models\User;

class DebitTransactionPolicy extends AdminPolicy
{
    public function view(User $user, DebitTransaction $debitTransaction): bool
    {
        return true;
    }

    public function update(User $user, DebitTransaction $debitTransaction): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, DebitTransaction $debitTransaction): bool
    {
        return $user->isAdmin();
    }
}
