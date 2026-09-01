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
use App\Models\OutstandingBatch;
use App\Models\OutstandingLine;
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

        OutstandingBatch::query()->where('kind', 'receivable')->delete();

        $this->createReceivableLine($fibreId, now()->toDateString(), 'Test Customer', '75000.00');

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

        OutstandingBatch::query()->where('kind', 'receivable')->delete();

        $summary = $this->dashboardSummary();

        $this->assertSame(Money::of('0'), $summary->receivables);
        $this->assertSame(Money::of('25000'), $summary->creditSales);
    }

    public function test_truncating_credit_transactions_zeros_credit_sales_but_not_receivables(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();
        $entityId = $this->jagadeesanEntityId();

        OutstandingBatch::query()->where('kind', 'receivable')->delete();

        $this->createReceivableLine($fibreId, now()->toDateString(), 'Receivable Customer', '30000.00');

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
        OutstandingBatch::query()->delete();
        OutstandingBatch::query()->delete();
        BankingSnapshot::query()->delete();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        Livewire::test(Dashboard::class)
            ->assertSee('₹0')
            ->assertSee('—');
    }

    public function test_dashboard_renders_receivables_after_sales_are_created(): void
    {
        $admin = $this->admin();
        $fibreId = $this->fibreUnitId();

        $this->actingAs($admin);
        app(UnitScope::class)->initializeForUser($admin);

        OutstandingBatch::query()->where('kind', 'receivable')->delete();

        $this->createReceivableLine($fibreId, now()->toDateString(), 'Livewire Customer', '12000.00');

        Livewire::test(Dashboard::class)
            ->assertSee('12,000');
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
            ->assertSee('1,000')
            ->dispatch('import-completed')
            ->assertSet('importRefreshVersion', 1);
    }

    public function test_banking_uses_snapshot_as_of_range_end(): void
    {
        BankingSnapshot::query()->delete();

        $admin = $this->admin();

        BankingSnapshot::factory()->create([
            'as_of_date' => now()->subMonths(2)->toDateString(),
            'current_balance' => '100000.00',
            'created_by' => $admin->id,
        ]);

        BankingSnapshot::factory()->create([
            'as_of_date' => now()->subMonth()->toDateString(),
            'current_balance' => '200000.00',
            'created_by' => $admin->id,
        ]);

        $summary = app(DashboardService::class)->summary(
            DateRange::fromState('custom', now()->subMonths(6)->toDateString(), now()->subMonth()->toDateString()),
            CostCenter::query()->pluck('id')->all(),
        );

        $this->assertSame('200000.00', $summary->bankCurrent);
    }

    public function test_outstandings_use_latest_import_as_of_range_end(): void
    {
        $fibreId = $this->fibreUnitId();

        OutstandingBatch::query()->where('kind', 'payable')->delete();

        $oldDate = now()->subMonths(2)->toDateString();
        $newDate = now()->subMonth()->toDateString();

        $this->createPayableLine($fibreId, $oldDate, 'Old Vendor', '5000.00');
        $this->createPayableLine($fibreId, $newDate, 'New Vendor', '8000.00');

        $summaryInRange = app(DashboardService::class)->summary(
            DateRange::fromState('custom', now()->subMonths(6)->toDateString(), now()->subMonth()->toDateString()),
            [$fibreId],
        );

        $this->assertSame(Money::of('8000'), $summaryInRange->payables);

        $summaryBeforeImport = app(DashboardService::class)->summary(
            DateRange::fromState('custom', now()->subMonths(6)->toDateString(), now()->subMonths(2)->toDateString()),
            [$fibreId],
        );

        $this->assertSame(Money::of('5000'), $summaryBeforeImport->payables);
    }

    public function test_shareholder_chart_excludes_vikas_when_fibre_unit_not_selected(): void
    {
        $chipsId = (int) CostCenter::query()->where('name', 'Chips Unit')->value('id');
        $range = DateRange::fromState('this_month');

        $bars = app(DashboardService::class)->shareholderBars([$chipsId], $range);
        $names = collect($bars)->pluck('name')->all();

        $this->assertNotContains('Vikas', $names);

        $fibreId = $this->fibreUnitId();
        $barsWithFibre = app(DashboardService::class)->shareholderBars([$fibreId], $range);

        $this->assertContains('Vikas', collect($barsWithFibre)->pluck('name')->all());
    }

    private function createReceivableLine(int $costCenterId, string $batchDate, string $party, string $amount): void
    {
        $batch = OutstandingBatch::query()->create([
            'kind' => 'receivable',
            'batch_date' => $batchDate,
            'created_by' => null,
        ]);

        OutstandingLine::query()->create([
            'batch_id' => $batch->id,
            'cost_center_id' => $costCenterId,
            'party_name' => $party,
            'amount' => $amount,
        ]);
    }

    private function createPayableLine(int $costCenterId, string $batchDate, string $party, string $amount): void
    {
        $batch = OutstandingBatch::query()->create([
            'kind' => 'payable',
            'batch_date' => $batchDate,
            'created_by' => null,
        ]);

        OutstandingLine::query()->create([
            'batch_id' => $batch->id,
            'cost_center_id' => $costCenterId,
            'party_name' => $party,
            'amount' => $amount,
        ]);
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
