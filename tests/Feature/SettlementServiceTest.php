<?php

namespace Tests\Feature;

use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\HistoricalAlamExpense;
use App\Services\SettlementService;
use App\Support\Money;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_computes_fibre_settlement_from_empty_transactions_without_error(): void
    {
        $this->seed(DatabaseSeeder::class);

        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();

        $result = app(SettlementService::class)->forCostCenter($fibre);

        $this->assertCount(4, $result->partners);
        $this->assertSame('Fibre Unit', $result->costCenter->name);
    }

    public function test_excludes_vikas_from_chips_partners_and_ubi_share(): void
    {
        $this->seed(DatabaseSeeder::class);

        $chips = CostCenter::query()->where('name', 'Chips Unit')->firstOrFail();
        $result = app(SettlementService::class)->forCostCenter($chips);

        $names = collect($result->partners)->map(fn ($partner) => $partner->entity->name)->all();

        $this->assertSame([
            'Shareholder - Jagadeesan',
            'Shareholder - Jagadeshwaran',
            'Shareholder - Vellingiri',
        ], $names);
        $this->assertTrue(collect($result->partners)->every(fn ($partner) => Money::cmp($partner->ubiShare, '0') === 0));
    }

    public function test_folds_historical_alam_into_fibre_alam_share_for_jagadeesan_and_jagadeshwaran(): void
    {
        $this->seed(DatabaseSeeder::class);

        $historicalTotal = (string) HistoricalAlamExpense::query()->sum('amount');
        $folded = Money::mul($historicalTotal, (string) config('hexagro.hist_alam_share_pct'));
        $expectedShare = Money::mul($folded, '0.5');

        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $result = app(SettlementService::class)->forCostCenter($fibre);

        $byName = collect($result->partners)->keyBy(fn ($partner) => $partner->entity->name);

        $this->assertSame(0, Money::cmp($byName['Shareholder - Jagadeesan']->alamShare, $expectedShare));
        $this->assertSame(0, Money::cmp($byName['Shareholder - Jagadeshwaran']->alamShare, $expectedShare));
        $this->assertSame(0, Money::cmp($byName['Shareholder - Vellingiri']->alamShare, Money::zero()));
        $this->assertSame(0, Money::cmp($byName['Vikas']->alamShare, Money::zero()));
        $this->assertSame(0, Money::cmp($result->alamNet, $folded));
    }

    public function test_applies_overall_adjustment_from_jagadeshwaran_to_vellingiri(): void
    {
        $this->seed(DatabaseSeeder::class);

        $overall = app(SettlementService::class)->overall();
        $byName = $overall->keyBy(fn ($row) => $row->entity->name);

        $this->assertSame(0, Money::cmp($byName['Shareholder - Jagadeshwaran']->adjustment, '-116980.000000'));
        $this->assertSame(0, Money::cmp($byName['Shareholder - Vellingiri']->adjustment, '116980.000000'));
        $this->assertSame(0, Money::cmp($byName['Shareholder - Jagadeesan']->adjustment, Money::zero()));
    }

    public function test_suggests_a_transfer_from_payer_to_receiver(): void
    {
        $this->seed(DatabaseSeeder::class);

        $vikas = Entity::query()->where('name', 'Vikas')->firstOrFail();
        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->firstOrFail();

        $transfers = app(SettlementService::class)->suggestedTransfers([
            ['entity' => $vikas, 'outstanding' => '-1000.00'],
            ['entity' => $jagadeesan, 'outstanding' => '1000.00'],
        ]);

        $this->assertCount(1, $transfers);
        $this->assertTrue($transfers[0]->from->is($vikas));
        $this->assertTrue($transfers[0]->to->is($jagadeesan));
        $this->assertSame(0, Money::cmp($transfers[0]->amount, '1000.00'));
    }
}
