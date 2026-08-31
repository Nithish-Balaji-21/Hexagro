<?php

namespace Tests\Feature;

use App\Enums\DebitCategory;
use App\Livewire\Dashboard;
use App\Livewire\Layout\UnitSwitcher;
use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\User;
use App\Support\UnitScope;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardUnitScopeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_debit_totals_respect_selected_unit(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibreId = CostCenter::query()->where('name', 'Fibre Unit')->value('id');
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');
        $entityId = Entity::query()->where('name', 'Shareholder - Jagadeesan')->value('id');

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '1000.00',
            'category' => DebitCategory::Expense,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $chipsId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '5000.00',
            'category' => DebitCategory::Expense,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(Dashboard::class)
            ->assertSee('6,000');

        app(UnitScope::class)->setSelectedUnits([$fibreId]);

        Livewire::test(Dashboard::class)
            ->assertSee('1,000')
            ->assertDontSee('5,000');
    }

    public function test_dashboard_refreshes_when_units_changed_event_dispatched(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibreId = CostCenter::query()->where('name', 'Fibre Unit')->value('id');
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');
        $entityId = Entity::query()->where('name', 'Shareholder - Jagadeesan')->value('id');

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '1000.00',
            'category' => DebitCategory::Expense,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $chipsId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '5000.00',
            'category' => DebitCategory::Expense,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(Dashboard::class)
            ->assertSee('6,000');

        app(UnitScope::class)->setSelectedUnits([$fibreId]);

        Livewire::test(Dashboard::class)
            ->dispatch('units-selection-changed')
            ->assertSet('unitScopeVersion', 1)
            ->assertSee('1,000')
            ->assertDontSee('5,000');
    }

    public function test_unit_switcher_toggle_dispatches_units_selection_changed(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(UnitSwitcher::class)
            ->call('toggleUnit', 'Fibre Unit')
            ->assertDispatched('units-selection-changed')
            ->assertSet('selectedUnits', ['Chips Unit', 'Washing Unit']);
    }

    public function test_unit_switcher_toggle_updates_session_for_fresh_dashboard_mount(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibreId = CostCenter::query()->where('name', 'Fibre Unit')->value('id');
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');
        $entityId = Entity::query()->where('name', 'Shareholder - Jagadeesan')->value('id');

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '1000.00',
            'category' => DebitCategory::Expense,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $chipsId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '5000.00',
            'category' => DebitCategory::Expense,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(UnitSwitcher::class)
            ->call('toggleUnit', 'Fibre Unit')
            ->assertDispatched('units-selection-changed');

        Livewire::test(Dashboard::class)
            ->assertSee('5,000')
            ->assertDontSee('1,000');
    }

    public function test_two_request_flow_switcher_toggle_then_dashboard_event(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibreId = CostCenter::query()->where('name', 'Fibre Unit')->value('id');
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');
        $entityId = Entity::query()->where('name', 'Shareholder - Jagadeesan')->value('id');

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '1000.00',
            'category' => DebitCategory::Expense,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $chipsId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '5000.00',
            'category' => DebitCategory::Expense,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(Dashboard::class)
            ->assertSee('6,000');

        Livewire::test(UnitSwitcher::class)
            ->call('toggleUnit', 'Fibre Unit')
            ->assertDispatched('units-selection-changed');

        Livewire::test(Dashboard::class)
            ->dispatch('units-selection-changed')
            ->assertSet('unitScopeVersion', 1)
            ->assertSee('5,000')
            ->assertDontSee('1,000');
    }
}
