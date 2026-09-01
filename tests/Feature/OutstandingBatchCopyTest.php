<?php

namespace Tests\Feature;

use App\Livewire\Transactions\PayablesIndex;
use App\Livewire\Transactions\ReceivablesIndex;
use App\Models\CostCenter;
use App\Models\OutstandingBatch;
use App\Models\OutstandingLine;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OutstandingBatchCopyTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_copy_payable_batch(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();

        $this->actingAs($user);

        $sourceBatch = OutstandingBatch::query()->create([
            'kind' => 'payable',
            'batch_date' => '2026-08-01',
            'created_by' => $user->id,
        ]);

        OutstandingLine::query()->create([
            'batch_id' => $sourceBatch->id,
            'cost_center_id' => $costCenter->id,
            'party_name' => 'Supplier Alpha',
            'amount' => '15000.00',
            'notes' => 'Invoice 101',
        ]);

        OutstandingLine::query()->create([
            'batch_id' => $sourceBatch->id,
            'cost_center_id' => $costCenter->id,
            'party_name' => 'Supplier Beta',
            'amount' => '25000.00',
            'notes' => 'Invoice 102',
        ]);

        Livewire::test(PayablesIndex::class)
            ->call('copyBatch', $sourceBatch->id)
            ->assertSet('showBatchForm', true)
            ->assertSet('editingBatchId', null)
            ->assertSet('lineRows.0.party', 'Supplier Alpha')
            ->assertSet('lineRows.0.amount', '15000')
            ->assertSet('lineRows.1.party', 'Supplier Beta')
            ->assertSet('lineRows.1.amount', '25000')
            ->set('lineRows.0.amount', '18000.00')
            ->set('formBatchDate', '2026-09-01')
            ->call('saveBatch')
            ->assertHasNoErrors()
            ->assertSet('showBatchForm', false);

        $this->assertSame(2, OutstandingBatch::query()->where('kind', 'payable')->count());

        $newBatch = OutstandingBatch::query()->where('kind', 'payable')->where('id', '!=', $sourceBatch->id)->firstOrFail();
        $this->assertSame('2026-09-01', $newBatch->batch_date->toDateString());
        $this->assertCount(2, $newBatch->lines);
        $this->assertDatabaseHas('outstanding_lines', [
            'batch_id' => $newBatch->id,
            'party_name' => 'Supplier Alpha',
            'amount' => '18000.00',
        ]);
    }

    public function test_user_can_copy_receivable_batch(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();

        $this->actingAs($user);

        $sourceBatch = OutstandingBatch::query()->create([
            'kind' => 'receivable',
            'batch_date' => '2026-08-10',
            'created_by' => $user->id,
        ]);

        OutstandingLine::query()->create([
            'batch_id' => $sourceBatch->id,
            'cost_center_id' => $costCenter->id,
            'party_name' => 'Customer Zenith',
            'amount' => '50000.00',
            'notes' => 'Pending payment',
        ]);

        Livewire::test(ReceivablesIndex::class)
            ->call('copyBatch', $sourceBatch->id)
            ->assertSet('showBatchForm', true)
            ->assertSet('editingBatchId', null)
            ->assertSet('lineRows.0.party', 'Customer Zenith')
            ->assertSet('lineRows.0.amount', '50000')
            ->set('formBatchDate', '2026-09-01')
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertSame(2, OutstandingBatch::query()->where('kind', 'receivable')->count());
    }

    public function test_user_can_load_lines_from_batch_inside_form(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();

        $this->actingAs($user);

        $existingBatch = OutstandingBatch::query()->create([
            'kind' => 'payable',
            'batch_date' => '2026-08-15',
            'created_by' => $user->id,
        ]);

        OutstandingLine::query()->create([
            'batch_id' => $existingBatch->id,
            'cost_center_id' => $costCenter->id,
            'party_name' => 'Vendor Gamma',
            'amount' => '12000.00',
            'notes' => 'Monthly retainer',
        ]);

        Livewire::test(PayablesIndex::class)
            ->call('openCreateBatch')
            ->assertSet('showBatchForm', true)
            ->call('loadLinesFromBatch', $existingBatch->id)
            ->assertSet('lineRows.0.party', 'Vendor Gamma')
            ->assertSet('lineRows.0.amount', '12000');
    }
}
