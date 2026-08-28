<?php

namespace Tests\Feature;

use App\Livewire\Transactions\DebitIndex;
use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DebitPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_debit_page_loads_for_authenticated_user(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($user)
            ->get('/debit')
            ->assertOk()
            ->assertSee('Debit');
    }

    public function test_admin_can_create_debit_transaction(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $unit = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $entity = Entity::query()->where('name', 'Payable to Alam')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(DebitIndex::class)
            ->call('openCreateForm')
            ->set('formDate', '2026-05-15')
            ->set('formCostCenterId', (string) $unit->id)
            ->set('formCategory', 'EXPENSE')
            ->set('formAccount', 'Employee Salaries')
            ->set('formPaidThroughId', (string) $entity->id)
            ->set('formDescription', 'Weekly wages')
            ->set('formAmount', '18279')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('debit_transactions', [
            'account' => 'Employee Salaries',
            'amount' => '18279.00',
            'cost_center_id' => $unit->id,
        ]);
    }

    public function test_viewer_cannot_open_debit_create_form(): void
    {
        $viewer = User::query()->where('name', 'Vikas')->firstOrFail();

        Livewire::actingAs($viewer)
            ->test(DebitIndex::class)
            ->call('openCreateForm')
            ->assertForbidden();
    }
}
