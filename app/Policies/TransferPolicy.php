<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy extends AdminPolicy
{
    public function view(User $user, Transfer $transfer): bool
    {
        return true;
    }

    public function update(User $user, Transfer $transfer): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Transfer $transfer): bool
    {
        return $user->isAdmin();
    }
}
