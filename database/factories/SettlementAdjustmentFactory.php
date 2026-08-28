<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\SettlementAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettlementAdjustment>
 */
class SettlementAdjustmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_entity_id' => Entity::factory(),
            'to_entity_id' => Entity::factory(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'note' => fake()->sentence(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
