<?php

namespace Database\Factories;

use App\Enums\CreditType;
use App\Models\CostCenter;
use App\Models\CreditTransaction;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'txn_date' => fake()->date(),
            'cost_center_id' => CostCenter::factory(),
            'credit_type' => CreditType::Sales,
            'received_to_entity_id' => Entity::factory(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'created_by' => User::factory()->admin(),
        ];
    }
}
