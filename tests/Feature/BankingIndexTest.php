<?php

namespace Tests\Feature;

use App\Livewire\Finance\BankingIndex;
use App\Models\BankingSnapshot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BankingIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_snapshot_form_does_not_include_alam_utilised_input(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(BankingIndex::class)
            ->call('openEditForm')
            ->assertDontSee('wire:model="formAlamUtilised"', false);
    }

    public function test_admin_can_save_snapshot_without_manual_alam_field(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(BankingIndex::class)
            ->set('formAsOf', '01/09/2026')
            ->set('formCurrent', '300000.00')
            ->set('formCcLimit', '5000000.00')
            ->set('formCcUtilised', '3500000.00')
            ->set('formTermLoan', '13000000.00')
            ->set('formTlLimit', '13500000.00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('banking_snapshots', [
            'as_of_date' => '2026-09-01',
            'current_balance' => '300000.00',
            'alam_utilised' => '0.00',
        ]);
    }

    public function test_admin_cannot_create_duplicate_snapshot_for_same_date(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(BankingIndex::class)
            ->set('formAsOf', '15/08/2026')
            ->set('formCurrent', '300000.00')
            ->set('formCcLimit', '5000000.00')
            ->set('formCcUtilised', '3500000.00')
            ->set('formTermLoan', '13000000.00')
            ->set('formTlLimit', '13500000.00')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(BankingIndex::class)
            ->set('formAsOf', '15/08/2026')
            ->set('formCurrent', '400000.00')
            ->set('formCcLimit', '5000000.00')
            ->set('formCcUtilised', '3500000.00')
            ->set('formTermLoan', '13000000.00')
            ->set('formTlLimit', '13500000.00')
            ->call('save')
            ->assertHasErrors(['formAsOf' => 'unique']);

        $this->assertSame(1, BankingSnapshot::query()->where('as_of_date', '2026-08-15')->count());
    }

    public function test_admin_can_edit_existing_snapshot_for_same_date(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(BankingIndex::class)
            ->set('formAsOf', '20/08/2026')
            ->set('formCurrent', '300000.00')
            ->set('formCcLimit', '5000000.00')
            ->set('formCcUtilised', '3500000.00')
            ->set('formTermLoan', '13000000.00')
            ->set('formTlLimit', '13500000.00')
            ->call('save')
            ->assertHasNoErrors();

        $snapshot = BankingSnapshot::query()->where('as_of_date', '2026-08-20')->firstOrFail();

        Livewire::test(BankingIndex::class)
            ->call('openEditForm', $snapshot->id)
            ->set('formCurrent', '450000.00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('banking_snapshots', [
            'id' => $snapshot->id,
            'as_of_date' => '2026-08-20',
            'current_balance' => '450000.00',
        ]);
    }

    public function test_banking_page_does_not_show_computed_alam_label(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(BankingIndex::class)
            ->assertDontSee('computed from transactions');
    }
}
