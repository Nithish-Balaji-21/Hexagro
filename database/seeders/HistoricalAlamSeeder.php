<?php

namespace Database\Seeders;

use App\Models\HistoricalAlamExpense;
use App\Models\User;
use Illuminate\Database\Seeder;

class HistoricalAlamSeeder extends Seeder
{
    /**
     * Seed Fibre Unit pre-settlement Alam expenses from the prototype RAW_HIST_ALAM set.
     */
    public function run(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        foreach ($this->rows() as $row) {
            HistoricalAlamExpense::query()->firstOrCreate(
                [
                    'txn_date' => $row[0],
                    'account' => $row[1],
                    'description' => $row[2],
                    'amount' => $row[3],
                ],
                ['created_by' => $admin->id],
            );
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function rows(): array
    {
        return [
            ['2026-04-04', 'Employee Salaries', 'Weekly labor wages', '7359.00'],
            ['2026-04-13', 'Construction Expense', 'JCB and tractor — Subramaniam', '8400.00'],
            ['2026-04-14', 'Vehicles', 'Forklift oil', '3700.00'],
            ['2026-04-14', 'Vehicles', 'Hose purchase amount & petrol', '450.00'],
            ['2026-04-18', 'Employee Salaries', 'Weekly labor wages', '14604.00'],
            ['2026-04-21', 'Electrical Expenses', 'Naveen Electricals', '935.00'],
            ['2026-04-22', 'Repairs and Maintenance', 'Crane for forklift — Nanbargal', '3500.00'],
            ['2026-04-24', 'Repairs and Maintenance', 'Patta drill & cutting — Chellam Agencies', '11500.00'],
            ['2026-04-24', 'Repairs and Maintenance', 'Alam labor & expenses book', '6350.00'],
            ['2026-04-25', 'Fuel Expense', 'Bowser diesel', '15308.00'],
            ['2026-05-01', 'Administration Expense', 'Jio recharge', '100.00'],
            ['2026-05-02', 'Employee Salaries', 'Weekly labor wages', '1666.00'],
            ['2026-05-09', 'Employee Salaries', 'Weekly labor wages', '16765.00'],
            ['2026-05-12', 'Electricity Bills', 'Electricity bill', '25462.00'],
            ['2026-05-15', 'Employee Salaries', 'Weekly labor wages', '18279.00'],
            ['2026-05-22', 'Raw Materials', 'Angel Traders', '62540.00'],
            ['2026-05-22', 'Transportation Expense', 'Straps — transportation', '500.00'],
            ['2026-05-23', 'Employee Salaries', 'Weekly labor wages', '17680.00'],
            ['2026-05-30', 'Employee Salaries', 'Weekly labor wages', '23387.00'],
            ['2026-05-31', 'IT and Internet Expenses', 'Jio recharge', '666.00'],
            ['2026-06-03', 'Repairs and Maintenance', 'New nut work', '2200.00'],
            ['2026-06-06', 'Employee Salaries', 'Weekly labor wages', '24928.00'],
            ['2026-06-09', 'Repairs and Maintenance', 'Hydraulic oil — 210 litres', '36750.00'],
            ['2026-06-13', 'Employee Salaries', 'Weekly labor wages', '21754.00'],
            ['2026-06-13', 'Employee Salaries - Loaders', 'Radha loader salary from Jun 1', '4117.00'],
            ['2026-06-20', 'Employee Salaries', 'Weekly salary + pickup drop', '14465.00'],
            ['2026-06-20', 'Labor Maintenance', '3 months labor rice — 5 people', '7200.00'],
            ['2026-06-21', 'Vehicles', 'Vehicle tyre powder, grease, fitting', '1500.00'],
            ['2026-06-21', 'Repairs and Maintenance', 'Hose, nuts, fittings, labor', '2600.00'],
            ['2026-06-21', 'Employee Salaries - Loaders', 'Radha loading, weigh bridge', '2799.00'],
        ];
    }
}
