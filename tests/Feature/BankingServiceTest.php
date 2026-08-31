<?php

namespace Tests\Feature;

use App\Enums\DebitCategory;
use App\Models\BankingSnapshot;
use App\Models\CostCenter;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\User;
use App\Services\BankingService;
use App\Support\Money;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BankingServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_alam_debit_increases_alam_utilised(): void
    {
        $admin = $this->admin();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $alam = Entity::query()->where('name', 'Payable to Alam')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-01',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $alam->id,
            'amount' => '50000.00',
            'created_by' => $admin->id,
            'category' => DebitCategory::RawMaterials,
        ]);

        $this->assertSame(0, Money::cmp(
            app(BankingService::class)->alamUtilisedAsOf('2026-08-09'),
            '50000.00',
        ));
    }

    public function test_alam_credit_decreases_alam_utilised(): void
    {
        $admin = $this->admin();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $alam = Entity::query()->where('name', 'Payable to Alam')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-01',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $alam->id,
            'amount' => '80000.00',
            'created_by' => $admin->id,
        ]);

        CreditTransaction::factory()->create([
            'txn_date' => '2026-08-05',
            'cost_center_id' => $fibre->id,
            'received_to_entity_id' => $alam->id,
            'amount' => '30000.00',
            'created_by' => $admin->id,
        ]);

        $this->assertSame(0, Money::cmp(
            app(BankingService::class)->alamUtilisedAsOf('2026-08-09'),
            '50000.00',
        ));
    }

    public function test_alam_utilised_respects_as_of_date_cutoff(): void
    {
        $admin = $this->admin();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $alam = Entity::query()->where('name', 'Payable to Alam')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-01',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $alam->id,
            'amount' => '10000.00',
            'created_by' => $admin->id,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-15',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $alam->id,
            'amount' => '20000.00',
            'created_by' => $admin->id,
        ]);

        $service = app(BankingService::class);

        $this->assertSame(0, Money::cmp($service->alamUtilisedAsOf('2026-08-09'), '10000.00'));
        $this->assertSame(0, Money::cmp($service->alamUtilisedAsOf('2026-08-20'), '30000.00'));
    }

    public function test_position_derives_alam_payable_from_ledger_balance(): void
    {
        $admin = $this->admin();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $alam = Entity::query()->where('name', 'Payable to Alam')->firstOrFail();
        $snapshot = BankingSnapshot::query()->where('as_of_date', '2026-08-09')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-01',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $alam->id,
            'amount' => '1461586.25',
            'created_by' => $admin->id,
        ]);

        $position = app(BankingService::class)->position($snapshot);

        $this->assertSame(0, Money::cmp($position->alamUtilised, '1461586.25'));
        $this->assertSame(0, Money::cmp($position->alamPayable, '-1461586.25'));
    }

    private function admin(): User
    {
        return User::query()->where('name', 'Jagadeesan')->firstOrFail();
    }
}
