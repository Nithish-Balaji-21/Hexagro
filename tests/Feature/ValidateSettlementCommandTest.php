<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ValidateSettlementCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reports_validation_failure_when_transactions_are_not_imported(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('hexagro:validate-settlement')
            ->expectsOutputToContain('Fibre Unit — Jagadeesan settlement comparison')
            ->expectsOutputToContain('FAIL')
            ->assertFailed();
    }
}
