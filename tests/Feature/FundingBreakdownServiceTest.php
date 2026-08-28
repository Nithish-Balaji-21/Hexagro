<?php

namespace Tests\Feature;

use App\Enums\DebitCategory;
use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\User;
use App\Services\FundingBreakdownService;
use App\Support\Money;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FundingBreakdownServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_zero_totals_when_no_transactions_exist(): void
    {
        $this->seed(ReferenceSeeder::class);

        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $rows = app(FundingBreakdownService::class)->forCostCenter($fibre);

        $this->assertCount(8, $rows);
        $this->assertTrue($rows->every(fn ($row) => Money::cmp($row->entityTotal, '0') === 0));
    }

    public function test_splits_expense_and_raw_materials_for_the_paid_through_entity(): void
    {
        $this->seed(ReferenceSeeder::class);

        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $fibre = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->firstOrFail();

        DebitTransaction::factory()->create([
            'txn_date' => '2026-06-01',
            'cost_center_id' => $fibre->id,
            'category' => DebitCategory::Expense,
            'account' => 'Fuel Expense',
            'paid_through_entity_id' => $jagadeesan->id,
            'amount' => '250.00',
            'created_by' => $admin->id,
        ]);

        DebitTransaction::factory()->rawMaterials()->create([
            'txn_date' => '2026-06-02',
            'cost_center_id' => $fibre->id,
            'paid_through_entity_id' => $jagadeesan->id,
            'amount' => '750.00',
            'created_by' => $admin->id,
        ]);

        $rows = app(FundingBreakdownService::class)->forCostCenter($fibre);
        $row = $rows->first(fn ($item) => $item->entity->is($jagadeesan));

        $this->assertSame(0, Money::cmp($row->expenses, '250.00'));
        $this->assertSame(0, Money::cmp($row->rawMaterials, '750.00'));
        $this->assertSame(0, Money::cmp($row->entityTotal, '1000.00'));
    }
}
