<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy extends AdminPolicy
{
    public function view(User $user, Purchase $purchase): bool
    {
        return true;
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin();
    }
}
