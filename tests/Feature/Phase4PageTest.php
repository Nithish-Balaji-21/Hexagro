<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Phase4PageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function phaseFourPagesProvider(): array
    {
        return [
            'transfers' => ['/transfers', 'Transfers'],
            'monthly spend' => ['/monthly-spend', 'Monthly Spend'],
            'settlement' => ['/settlement', 'Summary & Settlement'],
            'purchases' => ['/purchases', 'Purchases'],
            'sales' => ['/sales', 'Sales'],
            'banking' => ['/banking', 'Banking'],
            'ledger book' => ['/ledger-book', 'Ledger Book'],
            'historical alam' => ['/historical-alam', 'Historical Alam Expenses'],
        ];
    }

    #[DataProvider('phaseFourPagesProvider')]
    public function test_phase_four_page_loads_for_authenticated_user(string $path, string $heading): void
    {
        $user = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($user)
            ->get($path)
            ->assertOk()
            ->assertSee($heading);
    }

    public function test_phase_four_pages_require_authentication(): void
    {
        foreach (array_keys(self::phaseFourPagesProvider()) as $key) {
            [$path] = self::phaseFourPagesProvider()[$key];
            $this->get($path)->assertRedirect(route('login'));
        }
    }
}
