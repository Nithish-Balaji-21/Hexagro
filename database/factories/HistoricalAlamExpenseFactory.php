<?php

namespace Database\Factories;

use App\Models\HistoricalAlamExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistoricalAlamExpense>
 */
class HistoricalAlamExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'txn_date' => fake()->date(),
            'account' => 'Employee Salaries',
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'created_by' => User::factory()->admin(),
        ];
    }
}
