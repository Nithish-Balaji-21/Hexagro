<?php

namespace Tests\Feature;

use App\Livewire\Transactions\DebitIndex;
use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\User;
use App\Support\DateRange;
use App\Support\FiscalYear;
use App\Support\UnitScope;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DateRangePickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_default_preset_is_ytd(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(DebitIndex::class)
            ->assertSet('rangePreset', 'ytd');
    }

    public function test_ytd_preset_uses_fiscal_year_start(): void
    {
        Carbon::setTestNow('2026-08-31');

        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fyStart = FiscalYear::months()[0]['start']->toDateString();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(DebitIndex::class)
            ->call('setRangePreset', 'ytd')
            ->assertSet('rangePreset', 'ytd');

        $range = DateRange::fromState('ytd');

        $this->assertSame($fyStart, $range->from);
        $this->assertSame('2026-08-31', $range->to);
        $this->assertNotSame('2026-01-01', $range->from);

        Carbon::setTestNow();
    }

    public function test_seven_day_preset_filters_last_seven_days(): void
    {
        Carbon::setTestNow('2026-08-31');

        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-20',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $jagadeesan->id,
            'amount' => '100.00',
            'created_by' => $admin->id,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-28',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $jagadeesan->id,
            'amount' => '200.00',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(DebitIndex::class)
            ->call('setRangePreset', '7d')
            ->call('applyRangePicker')
            ->assertSet('rangePreset', '7d')
            ->assertSee('₹200')
            ->assertDontSee('₹100');

        Carbon::setTestNow();
    }

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
            ->assertSee('₹200')
            ->assertDontSee('₹100');
    }

    public function test_detect_preset_identifies_matching_ranges(): void
    {
        Carbon::setTestNow('2026-08-31');

        $this->assertSame('today', DateRange::detectPreset('2026-08-31', '2026-08-31'));
        $this->assertSame('7d', DateRange::detectPreset('2026-08-25', '2026-08-31'));
        $this->assertSame('ytd', DateRange::detectPreset('2026-04-01', '2026-08-31'));
        $this->assertSame('custom', DateRange::detectPreset('2026-01-10', '2026-02-15'));

        Carbon::setTestNow();
    }

    public function test_update_picker_dates_auto_detects_preset(): void
    {
        Carbon::setTestNow('2026-08-31');

        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(DebitIndex::class)
            ->call('updatePickerDates', '2026-08-25', '2026-08-31')
            ->assertSet('pickerPreset', '7d');

        Carbon::setTestNow();
    }
}
