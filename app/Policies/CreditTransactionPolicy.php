<?php

namespace App\Policies;

use App\Models\CreditTransaction;
use App\Models\User;

class CreditTransactionPolicy extends AdminPolicy
{
    public function view(User $user, CreditTransaction $creditTransaction): bool
    {
        return true;
    }

    public function update(User $user, CreditTransaction $creditTransaction): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, CreditTransaction $creditTransaction): bool
    {
        return $user->isAdmin();
    }
}
