<?php

namespace Database\Seeders;

use App\Models\BankingSnapshot;
use App\Models\User;
use Illuminate\Database\Seeder;

class BankingSeeder extends Seeder
{
    /**
     * Seed the prototype banking snapshot (as of 2026-08-09).
     */
    public function run(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        BankingSnapshot::query()->firstOrCreate(
            ['as_of_date' => '2026-08-09'],
            [
                'cc_limit' => '5000000.00',
                'cc_utilised' => '3718510.00',
                'current_balance' => '274018.00',
                'term_loan' => '13326000.00',
                'tl_limit' => '13500000.00',
                'alam_utilised' => '0.00',
                'created_by' => $admin->id,
            ],
        );
    }
}
