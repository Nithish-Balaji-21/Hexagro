<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Transfer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportExcelCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->fixturePath = storage_path('framework/testing/hexagro-import-sample.xlsx');
        $this->createFixtureWorkbook($this->fixturePath);
    }

    public function test_dry_run_does_not_write_transactions(): void
    {
        $this->artisan('hexagro:import-excel', [
            'file' => $this->fixturePath,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, DebitTransaction::query()->count());
        $this->assertSame(0, CreditTransaction::query()->count());
        $this->assertSame(0, Transfer::query()->count());
        $this->assertSame(0, Purchase::query()->count());
        $this->assertSame(0, Sale::query()->count());
    }

    public function test_imports_debit_credit_transfer_and_outstanding_rows(): void
    {
        $this->artisan('hexagro:import-excel', [
            'file' => $this->fixturePath,
        ])->assertSuccessful();

        $this->assertSame(2, DebitTransaction::query()->count());
        $this->assertSame(1, CreditTransaction::query()->count());
        $this->assertSame(1, Transfer::query()->count());
        $this->assertSame(1, Purchase::query()->count());
        $this->assertSame(1, Sale::query()->count());

        $this->assertDatabaseHas('debit_transactions', [
            'account' => 'Fuel Expense',
            'amount' => '1000.00',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'description' => 'Thara Substrates',
            'amount' => '50000.00',
        ]);

        $this->assertDatabaseHas('transfers', [
            'amount' => '87671.00',
        ]);

        $this->assertDatabaseHas('purchases', [
            'vendor_name' => 'HP Cocos',
            'total_billed' => '346000.00',
        ]);

        $this->assertDatabaseHas('sales', [
            'customer_name' => 'Sakthi Mariamman',
            'total_invoiced' => '484690.00',
        ]);
    }

    public function test_transfer_fund_rows_do_not_land_in_debit_or_credit_tables(): void
    {
        $this->artisan('hexagro:import-excel', [
            'file' => $this->fixturePath,
        ])->assertSuccessful();

        $this->assertSame(0, DebitTransaction::query()->where('account', 'Transfer Fund')->count());
        $this->assertSame(0, CreditTransaction::query()->where('description', 'Loan Recovery')->count());
    }

    private function createFixtureWorkbook(string $path): void
    {
        $spreadsheet = new Spreadsheet;

        $debit = $spreadsheet->getActiveSheet();
        $debit->setTitle('Debit');
        $debit->fromArray([
            ['Date', 'Cost Center', 'Type', 'Account', 'Paid Through', 'Description', 'Total Amount (₹)'],
            ['2026-06-15', 'Fibre Unit', 'Expense', 'Fuel Expense', 'Shareholder - Jagadeesan', 'Bowser diesel', 1000],
            ['2026-05-22', 'Fibre Unit', 'Raw Materials', 'Raw Materials', 'Payable to Alam', 'Angel Traders', 62540],
            ['2026-07-02', 'Chips Unit', 'Transfer Fund', 'Transfer Fund', 'Union Bank - CC', 'Loan Recovery', 87671],
        ]);

        $credit = $spreadsheet->createSheet();
        $credit->setTitle('Credit');
        $credit->fromArray([
            ['Date', 'Cost Center', 'Type', 'Received To', 'Description', 'Amount'],
            ['2026-07-26', 'Chips Unit', 'Sales', 'Union Bank - Current', 'Thara Substrates', 50000],
            ['2026-07-02', 'Chips Unit', 'Transfer Fund', 'Union Bank - Term Loan', 'Loan Recovery', 87671],
        ]);

        $outstanding = $spreadsheet->createSheet();
        $outstanding->setTitle('Outstanding');
        $outstanding->fromArray([
            ['Outstanding payments (unpaid — informational, not part of spend or settlement)'],
            [],
            ['Item / Party', 'Cost Center', 'Amount (₹)', 'Notes'],
            ['Sakthi Mariamman', 'Washing Unit', 484690, ''],
            ['HP Cocos', 'Fibre Unit', 346000, 'Outstanding vendor bill'],
        ]);

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }
}
