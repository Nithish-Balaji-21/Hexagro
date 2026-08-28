<?php

namespace Tests\Feature;

use App\Livewire\Transactions\CreditIndex;
use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreditPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_credit_page_loads_for_authenticated_user(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($user)
            ->get('/credit')
            ->assertOk()
            ->assertSee('Credit');
    }

    public function test_admin_can_create_credit_transaction(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $unit = CostCenter::query()->where('name', 'Fibre Unit')->firstOrFail();
        $entity = Entity::query()->where('name', 'Union Bank - Current')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(CreditIndex::class)
            ->call('openCreateForm')
            ->set('formDate', '2026-07-26')
            ->set('formCostCenterId', (string) $unit->id)
            ->set('formCreditType', 'SALES')
            ->set('formReceivedToId', (string) $entity->id)
            ->set('formDescription', 'Thara Substrates')
            ->set('formAmount', '50000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('credit_transactions', [
            'description' => 'Thara Substrates',
            'amount' => '50000.00',
            'cost_center_id' => $unit->id,
        ]);
    }
}
