<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\SettlementLedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettlementLedgerEntry>
 */
class SettlementLedgerEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'txn_date' => fake()->date(),
            'unit_scope' => 'Fibre Unit',
            'from_entity_id' => Entity::factory(),
            'to_entity_id' => Entity::factory(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'note' => fake()->optional()->sentence(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
