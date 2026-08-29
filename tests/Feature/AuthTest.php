<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_sees_login_with_four_user_cards(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Sign in');
        $response->assertSee('Jagadeesan');
        $response->assertSee('Jagadeshwaran');
        $response->assertSee('Vellingiri');
        $response->assertSee('Vikas');
    }

    public function test_click_through_login_redirects_to_dashboard(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        Livewire::test(Login::class)
            ->call('loginAs', $user->id)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/');
    }

    public function test_logout_clears_session_and_redirects_to_login(): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_sees_switch_user_on_dashboard(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Switch user');
    }

    public function test_viewer_does_not_see_switch_user_on_dashboard(): void
    {
        $viewer = User::query()->where('name', 'Vikas')->firstOrFail();

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Switch user')
            ->assertSee('Log out');
    }

    public function test_switch_user_logs_out_admin_and_returns_to_login(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('switch-user'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_viewer_cannot_use_switch_user_route(): void
    {
        $viewer = User::query()->where('name', 'Vikas')->firstOrFail();

        $this->actingAs($viewer)
            ->post(route('switch-user'))
            ->assertForbidden();
    }
}
