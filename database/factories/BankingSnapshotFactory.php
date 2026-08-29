<?php

namespace Database\Factories;

use App\Models\BankingSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankingSnapshot>
 */
class BankingSnapshotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $limit = '5000000.00';

        return [
            'as_of_date' => fake()->date(),
            'cc_limit' => $limit,
            'cc_utilised' => fake()->randomFloat(2, 0, 5000000),
            'current_balance' => fake()->randomFloat(2, 0, 1000000),
            'term_loan' => fake()->randomFloat(2, 0, 20000000),
            'tl_limit' => '13500000.00',
            'alam_utilised' => fake()->randomFloat(2, 0, 2000000),
            'created_by' => User::factory()->admin(),
        ];
    }
}
