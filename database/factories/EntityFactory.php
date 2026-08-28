<?php

namespace Database\Factories;

use App\Enums\EntityType;
use App\Models\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entity>
 */
class EntityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'name' => 'Shareholder - '.$name,
            'short_name' => $name,
            'entity_type' => EntityType::Shareholder,
            'is_active' => true,
        ];
    }

    public function bankAccount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->unique()->company().' Bank',
            'short_name' => 'Bank — '.fake()->lexify('??'),
            'entity_type' => EntityType::BankAccount,
        ]);
    }

    public function alam(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Payable to Alam',
            'short_name' => 'Alam',
            'entity_type' => EntityType::NonShareholderFunder,
        ]);
    }
}
