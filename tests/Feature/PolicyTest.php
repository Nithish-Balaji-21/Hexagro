<?php

namespace Tests\Feature;

use App\Models\DebitTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_viewer_cannot_create_financial_records(): void
    {
        $viewer = User::query()->where('name', 'Vikas')->firstOrFail();

        $this->assertFalse($viewer->can('create', DebitTransaction::class));
        $this->assertFalse($viewer->can('update', new DebitTransaction));
        $this->assertFalse($viewer->can('delete', new DebitTransaction));
    }

    public function test_admin_can_create_financial_records(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->assertTrue($admin->can('create', DebitTransaction::class));
        $this->assertTrue($admin->can('update', new DebitTransaction));
        $this->assertTrue($admin->can('delete', new DebitTransaction));
    }

    public function test_all_authenticated_users_can_view_records(): void
    {
        $viewer = User::query()->where('name', 'Vikas')->firstOrFail();

        $this->assertTrue($viewer->can('viewAny', DebitTransaction::class));
        $this->assertTrue($viewer->can('view', new DebitTransaction));
    }
}
