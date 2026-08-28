<?php

namespace App\Policies;

use App\Models\HistoricalAlamExpense;
use App\Models\User;

class HistoricalAlamExpensePolicy extends AdminPolicy
{
    public function view(User $user, HistoricalAlamExpense $historicalAlamExpense): bool
    {
        return true;
    }

    public function update(User $user, HistoricalAlamExpense $historicalAlamExpense): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, HistoricalAlamExpense $historicalAlamExpense): bool
    {
        return $user->isAdmin();
    }
}
