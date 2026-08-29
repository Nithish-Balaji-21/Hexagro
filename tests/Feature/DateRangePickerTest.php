<?php

namespace Tests\Feature;

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

class DateRangePickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_apply_range_picker_filters_debit_transactions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-06-01',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $jagadeesan->id,
            'amount' => '100.00',
            'created_by' => $admin->id,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-01',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $jagadeesan->id,
            'amount' => '200.00',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(DebitIndex::class)
            ->set('pickerFrom', '2026-08-01')
            ->set('pickerTo', '2026-08-31')
            ->set('pickerPreset', 'custom')
            ->call('applyRangePicker')
            ->assertSet('rangePreset', 'custom')
            ->assertSet('rangeFrom', '2026-08-01')
            ->assertSet('rangeTo', '2026-08-31')
            ->assertSee('200.00')
            ->assertDontSee('100.00');
    }
}
