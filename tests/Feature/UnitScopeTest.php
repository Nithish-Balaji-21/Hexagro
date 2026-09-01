<?php

namespace Tests\Feature;

use App\Enums\DebitCategory;
use App\Livewire\Layout\UnitSwitcher;
use App\Livewire\Reports\SettlementIndex;
use App\Livewire\Reports\SummaryIndex;
use App\Livewire\Transactions\DebitIndex;
use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\User;
use App\Support\UnitScope;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitScopeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_sees_all_three_units(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);

        $visible = app(UnitScope::class)->visibleUnits();

        $this->assertCount(3, $visible);
        $this->assertSame(['Chips Unit', 'Fibre Unit', 'Washing Unit'], $visible->pluck('name')->all());

        Livewire::test(UnitSwitcher::class)
            ->assertSet('locked', false)
            ->assertCount('visibleUnits', 3);
    }

    public function test_vikas_sees_fibre_unit_only(): void
    {
        $vikas = User::query()->where('name', 'Vikas')->firstOrFail();

        $this->actingAs($vikas);

        $visible = app(UnitScope::class)->visibleUnits();

        $this->assertCount(1, $visible);
        $this->assertSame('Fibre Unit', $visible->first()?->name);

        Livewire::test(UnitSwitcher::class)
            ->assertSet('locked', true)
            ->assertCount('visibleUnits', 1);
    }

    public function test_selected_units_default_to_all_visible_units(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);

        $unitScope = app(UnitScope::class);
        $unitScope->initializeForUser($admin);

        $this->assertCount(3, $unitScope->selectedUnitIds());
        $this->assertTrue($unitScope->isAllSelected());
    }

    public function test_unit_switcher_toggles_unit_in_and_out_of_selection(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(UnitSwitcher::class)
            ->assertSet('selectedUnits', ['Chips Unit', 'Fibre Unit', 'Washing Unit'])
            ->call('toggleUnit', 'Fibre Unit')
            ->assertSet('selectedUnits', ['Chips Unit', 'Washing Unit'])
            ->call('toggleUnit', 'Chips Unit')
            ->assertSet('selectedUnits', ['Washing Unit']);
    }

    public function test_unit_switcher_refuses_to_remove_last_remaining_selected_unit(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);
        app(UnitScope::class)->setSelectedUnitNames(['Fibre Unit']);

        Livewire::test(UnitSwitcher::class)
            ->assertSet('selectedUnits', ['Fibre Unit'])
            ->call('toggleUnit', 'Fibre Unit')
            ->assertSet('selectedUnits', ['Fibre Unit']); // Guard refused deselecting last unit
    }

    public function test_unit_switcher_select_all_selects_every_visible_unit(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);
        app(UnitScope::class)->setSelectedUnitNames(['Chips Unit']);

        Livewire::test(UnitSwitcher::class)
            ->assertSet('selectedUnits', ['Chips Unit'])
            ->call('selectAll')
            ->assertSet('selectedUnits', ['Chips Unit', 'Fibre Unit', 'Washing Unit']);
    }

    public function test_unit_scope_sanitizer_falls_back_if_empty_or_invalid(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);
        $unitScope = app(UnitScope::class);

        $sanitizedEmpty = $unitScope->sanitizeUnitNames([]);
        $this->assertSame(['Chips Unit', 'Fibre Unit', 'Washing Unit'], $sanitizedEmpty);

        $sanitizedInvalid = $unitScope->sanitizeUnitNames(['Invalid Unit Name']);
        $this->assertSame(['Chips Unit', 'Fibre Unit', 'Washing Unit'], $sanitizedInvalid);
    }

    public function test_dashboard_renders_single_unit_switcher(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('All Units');

        $this->assertSame(1, substr_count($response->getContent(), 'unit-switch multi'));
    }

    public function test_debit_page_renders_single_unit_switcher(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $response = $this->actingAs($admin)->get('/debit');

        $response->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'unit-switch multi'));
    }

    public function test_debit_page_refreshes_when_units_changed_event_dispatched(): void
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
            'description' => 'Fibre-only debit txn',
            'amount' => '1000.00',
            'category' => DebitCategory::Expense,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $chipsId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'description' => 'Chips-only debit txn',
            'amount' => '5000.00',
            'category' => DebitCategory::Expense,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(DebitIndex::class)
            ->assertSee('Fibre-only debit txn')
            ->assertSee('Chips-only debit txn');

        app(UnitScope::class)->setSelectedUnits([$fibreId]);

        Livewire::test(DebitIndex::class)
            ->dispatch('units-selection-changed')
            ->assertSet('unitScopeVersion', 1)
            ->assertSee('Fibre-only debit txn')
            ->assertDontSee('Chips-only debit txn');
    }

    public function test_units_changed_resets_stale_unit_tab_on_debit_page(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibreId = CostCenter::query()->where('name', 'Fibre Unit')->value('id');
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(DebitIndex::class)
            ->set('unitTab', (string) $fibreId)
            ->call('refreshUnitScope')
            ->assertSet('unitTab', '');
    }

    public function test_units_changed_sets_unit_tab_when_only_one_unit_selected(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibreId = CostCenter::query()->where('name', 'Fibre Unit')->value('id');
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');

        $this->actingAs($admin);
        app(UnitScope::class)->setSelectedUnits([$chipsId]);

        Livewire::test(DebitIndex::class)
            ->set('unitTab', (string) $fibreId)
            ->call('refreshUnitScope')
            ->assertSet('unitTab', (string) $chipsId);
    }

    public function test_settlement_mount_uses_scoped_units_not_all_visible(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');

        $this->actingAs($admin);
        app(UnitScope::class)->setSelectedUnits([$chipsId]);

        Livewire::test(SettlementIndex::class)
            ->assertSet('selectedTab', (string) $chipsId);
    }

    public function test_summary_mount_uses_scoped_units_not_all_visible(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $chipsId = CostCenter::query()->where('name', 'Chips Unit')->value('id');

        $this->actingAs($admin);
        app(UnitScope::class)->setSelectedUnits([$chipsId]);

        Livewire::test(SummaryIndex::class)
            ->assertSet('selectedTab', (string) $chipsId);
    }
}
