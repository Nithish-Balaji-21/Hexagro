<?php

namespace Database\Factories;

use App\Models\CostCenter;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $billed = fake()->randomFloat(2, 1000, 100000);

        return [
            'cost_center_id' => CostCenter::factory(),
            'vendor_name' => fake()->company(),
            'total_billed' => $billed,
            'total_paid' => fake()->randomFloat(2, 0, $billed),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function tbd(): static
    {
        return $this->state(fn (array $attributes): array => [
            'total_billed' => null,
            'total_paid' => 0,
        ]);
    }
}
