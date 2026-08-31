<?php

namespace Tests\Feature;

use App\Enums\CreditType;
use App\Enums\DebitCategory;
use App\Livewire\Dashboard;
use App\Models\BankingSnapshot;
use App\Models\CostCenter;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\Dto\DashboardSummary;
use App\Support\DateRange;
use App\Support\Money;
use App\Support\UnitScope;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardDynamicDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_truncating_sales_zeros_receivables_but_not_credit_sales(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();
        $entityId = $this->jagadeesanEntityId();

        Sale::factory()->create([
            'cost_center_id' => $fibreId,
            'customer_name' => 'Test Customer',
            'total_invoiced' => '75000.00',
            'total_received' => '0.00',
        ]);

        CreditTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'received_to_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '25000.00',
            'credit_type' => CreditType::Sales,
        ]);

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('75000'), $summary->receivables);
        $this->assertSame(Money::of('25000'), $summary->creditSales);

        Sale::query()->delete();

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('0'), $summary->receivables);
        $this->assertSame(Money::of('25000'), $summary->creditSales);
    }

    public function test_truncating_credit_transactions_zeros_credit_sales_but_not_receivables(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();
        $entityId = $this->jagadeesanEntityId();

        Sale::factory()->create([
            'cost_center_id' => $fibreId,
            'customer_name' => 'Receivable Customer',
            'total_invoiced' => '40000.00',
            'total_received' => '10000.00',
        ]);

        CreditTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'received_to_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '18000.00',
            'credit_type' => CreditType::Sales,
        ]);

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('30000'), $summary->receivables);
        $this->assertSame(Money::of('18000'), $summary->creditSales);

        CreditTransaction::query()->delete();

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('30000'), $summary->receivables);
        $this->assertSame(Money::of('0'), $summary->creditSales);
    }

    public function test_truncating_debit_transactions_zeros_debit_totals(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();
        $entityId = $this->jagadeesanEntityId();

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '3200.00',
            'category' => DebitCategory::Expense,
        ]);

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '6800.00',
            'category' => DebitCategory::RawMaterials,
        ]);

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('3200'), $summary->debitExpense);
        $this->assertSame(Money::of('6800'), $summary->debitRaw);

        DebitTransaction::query()->delete();

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('0'), $summary->debitExpense);
        $this->assertSame(Money::of('0'), $summary->debitRaw);
    }

    public function test_deleting_banking_snapshots_returns_null_banking_values(): void
    {
        BankingSnapshot::query()->delete();

        $summary = $this->dashboardSummary();

        $this->assertNull($summary->bankCurrent);
        $this->assertNull($summary->bankCcLimit);
    }

    public function test_date_range_excludes_out_of_range_credit_transactions(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();
        $entityId = $this->jagadeesanEntityId();

        CreditTransaction::factory()->create([
            'txn_date' => now()->subYears(2)->toDateString(),
            'cost_center_id' => $fibreId,
            'received_to_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '99999.00',
            'credit_type' => CreditType::Sales,
        ]);

        CreditTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'received_to_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '1500.00',
            'credit_type' => CreditType::Sales,
        ]);

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('1500'), $summary->creditSales);
        $this->assertNotSame(Money::of('99999'), $summary->creditSales);
    }

    public function test_zero_amounts_render_as_rupees_zero_on_dashboard(): void
    {
        $admin = $this->admin();

        DebitTransaction::query()->delete();
        CreditTransaction::query()->delete();
        Sale::query()->delete();
        Purchase::query()->delete();
        BankingSnapshot::query()->delete();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(Dashboard::class)
            ->assertSee('₹0.00')
            ->assertSee('—');
    }

    public function test_dashboard_renders_receivables_after_sales_are_created(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Sale::factory()->create([
            'cost_center_id' => $fibreId,
            'customer_name' => 'Livewire Customer',
            'total_invoiced' => '12000.00',
            'total_received' => '0.00',
        ]);

        Livewire::test(Dashboard::class)
            ->assertSee('12,000.00');
    }

    public function test_dashboard_refreshes_after_import_completed_event(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();
        $entityId = $this->jagadeesanEntityId();

        DebitTransaction::query()->delete();

        DebitTransaction::factory()->create([
            'txn_date' => now()->toDateString(),
            'cost_center_id' => $fibreId,
            'paid_through_entity_id' => $entityId,
            'created_by' => $admin->id,
            'amount' => '1000.00',
            'category' => DebitCategory::Expense,
        ]);

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(Dashboard::class)
            ->assertSee('1,000.00')
            ->dispatch('import-completed')
            ->assertSet('importRefreshVersion', 1);
    }

    private function dashboardSummary(): DashboardSummary
    {
        $unitIds = CostCenter::query()->pluck('id')->all();

        return app(DashboardService::class)->summary(
            DateRange::fromState('ytd'),
            $unitIds,
        );
    }

    private function admin(): User
    {
        return User::query()->where('name', 'Jagadeesan')->firstOrFail();
    }

    private function fibreUnitId(): int
    {
        return (int) CostCenter::query()->where('name', 'Fibre Unit')->value('id');
    }

    private function jagadeesanEntityId(): int
    {
        return (int) Entity::query()->where('name', 'Shareholder - Jagadeesan')->value('id');
    }
}
