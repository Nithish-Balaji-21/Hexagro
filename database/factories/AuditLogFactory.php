<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_name' => 'debit_transactions',
            'record_id' => fake()->numberBetween(1, 1000),
            'action' => AuditAction::Create,
            'changed_by' => User::factory()->admin(),
            'before_data' => null,
            'after_data' => ['amount' => '100.00'],
            'changed_at' => now(),
        ];
    }
}
