<?php

namespace Database\Factories;

use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'txn_date' => fake()->date(),
            'cost_center_id' => CostCenter::factory(),
            'from_entity_id' => Entity::factory(),
            'to_entity_id' => Entity::factory(),
            'note' => fake()->optional()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'created_by' => User::factory()->admin(),
        ];
    }
}
