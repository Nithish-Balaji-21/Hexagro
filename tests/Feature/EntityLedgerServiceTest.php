<?php

namespace Tests\Feature;

use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\User;
use App\Services\BankingService;
use App\Services\EntityLedgerService;
use App\Support\Money;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EntityLedgerServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_an_empty_ledger_when_the_entity_has_no_movements(): void
    {
        $this->seed(DatabaseSeeder::class);

        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->firstOrFail();
        $service = app(EntityLedgerService::class);

        $this->assertCount(0, $service->rows($jagadeesan->id));
        $this->assertSame(0, Money::cmp($service->closingBalance($jagadeesan->id), '0'));
    }

    public function test_credits_a_debit_paid_through_the_entity_and_tracks_running_balance(): void
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
            'description' => 'Bowser diesel',
            'amount' => '1000.00',
            'created_by' => $admin->id,
        ]);

        $rows = app(EntityLedgerService::class)->rows($jagadeesan->id);

        $this->assertCount(1, $rows);
        $this->assertSame(0, Money::cmp($rows->first()->credit, '1000.00'));
        $this->assertSame(0, Money::cmp($rows->first()->debit, '0'));
        $this->assertSame(0, Money::cmp($rows->first()->runningBalance, '1000.00'));
        $this->assertSame('Paid for Fuel Expense — Bowser diesel', $rows->first()->particulars);
    }

    public function test_derives_cc_available_and_alam_payable_from_the_latest_snapshot(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $alam = Entity::query()->where('name', 'Payable to Alam')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-08-01',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $alam->id,
            'amount' => '1461586.25',
            'created_by' => $admin->id,
        ]);

        $position = app(BankingService::class)->current();

        $this->assertNotNull($position);
        $this->assertSame('2026-08-09', $position->snapshot->as_of_date->toDateString());
        $this->assertSame(0, Money::cmp($position->ccAvailable, '1281490.00'));
        $this->assertSame(0, Money::cmp($position->alamUtilised, '1461586.25'));
        $this->assertSame(0, Money::cmp($position->alamPayable, '-1461586.25'));
    }
}
