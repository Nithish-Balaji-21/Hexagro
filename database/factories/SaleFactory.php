<?php

namespace Database\Factories;

use App\Models\CostCenter;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiced = fake()->randomFloat(2, 1000, 100000);

        return [
            'cost_center_id' => CostCenter::factory(),
            'customer_name' => fake()->company(),
            'total_invoiced' => $invoiced,
            'total_received' => fake()->randomFloat(2, 0, $invoiced),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
