<?php

namespace Tests\Feature;

use App\Livewire\Layout\UnitSwitcher;
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

    public function test_unit_switcher_toggles_selection(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibreId = app(UnitScope::class)->visibleUnits($admin)->firstWhere('name', 'Fibre Unit')?->id;

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(UnitSwitcher::class)
            ->call('toggleUnit', $fibreId)
            ->assertSet('selectedIds', fn (array $ids): bool => count($ids) === 2 && ! in_array($fibreId, $ids, true));
    }
}
