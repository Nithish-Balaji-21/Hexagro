<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ValidateSettlementCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_prints_ready_for_zoho_export(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('hexagro:validate-settlement')
            ->expectsOutput('ready for Zoho export')
            ->assertSuccessful();
    }
}
