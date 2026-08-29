<?php

namespace Tests\Feature;

use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\LedgerEntry;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LedgerRebuildTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creating_a_debit_materializes_a_ledger_entry(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->firstOrFail();

        $this->actingAs($admin);

        DebitTransaction::factory()->create([
            'txn_date' => '2026-06-15',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $jagadeesan->id,
            'account' => 'Fuel Expense',
            'description' => 'Bowser diesel',
            'amount' => '1000.00',
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'entity_id' => $jagadeesan->id,
            'source_table' => 'debit_transactions',
            'signed_amount' => '1000.00',
        ]);
    }

    public function test_rebuild_command_rebuilds_all_entities(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-06-15',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $jagadeesan->id,
            'account' => 'Fuel Expense',
            'amount' => '500.00',
            'created_by' => $admin->id,
        ]);

        LedgerEntry::query()->delete();

        $this->artisan('hexagro:rebuild-ledger')->assertSuccessful();

        $this->assertDatabaseHas('ledger_entries', [
            'entity_id' => $jagadeesan->id,
            'signed_amount' => '500.00',
        ]);
    }
}
