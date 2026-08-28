<?php

namespace Database\Factories;

use App\Enums\DebitCategory;
use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebitTransaction>
 */
class DebitTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'txn_date' => fake()->date(),
            'cost_center_id' => CostCenter::factory(),
            'category' => DebitCategory::Expense,
            'account' => 'Employee Salaries',
            'paid_through_entity_id' => Entity::factory(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'created_by' => User::factory()->admin(),
        ];
    }

    public function rawMaterials(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => DebitCategory::RawMaterials,
            'account' => 'Raw Materials',
        ]);
    }
}
