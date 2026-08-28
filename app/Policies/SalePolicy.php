<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends AdminPolicy
{
    public function view(User $user, Sale $sale): bool
    {
        return true;
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }
}
