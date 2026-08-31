<?php

namespace Tests\Feature;

use App\Livewire\Import\ExcelImportModal;
use App\Livewire\Import\ImportIndex;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Services\Import\ExcelImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportModalTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->fixturePath = storage_path('framework/testing/hexagro-import-modal.xlsx');
        $this->createFixtureWorkbook($this->fixturePath);
    }

    public function test_admin_can_preview_workbook_rows(): void
    {
        Storage::fake('local');
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ExcelImportModal::class)
            ->call('open', 'workbook')
            ->set('workbook', $this->uploadedFixture())
            ->call('preview')
            ->assertSet('step', 2)
            ->assertSee('Valid rows');
    }

    public function test_admin_can_confirm_debit_import_from_modal(): void
    {
        Storage::fake('local');
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ExcelImportModal::class)
            ->call('open', 'debit')
            ->set('workbook', $this->uploadedFixture())
            ->call('preview')
            ->call('confirmImport')
            ->assertSet('show', false);

        $this->assertSame(2, DebitTransaction::query()->count());
        $this->assertSame(0, CreditTransaction::query()->count());
    }

    public function test_viewer_cannot_open_import_modal(): void
    {
        $viewer = User::query()->where('name', 'Vikas')->firstOrFail();

        Livewire::actingAs($viewer)
            ->test(ExcelImportModal::class)
            ->call('open', 'debit')
            ->assertForbidden();
    }

    public function test_import_page_requires_admin(): void
    {
        $viewer = User::query()->where('name', 'Vikas')->firstOrFail();

        $this->actingAs($viewer)
            ->get('/import')
            ->assertForbidden();
    }

    public function test_admin_can_access_import_page(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin)
            ->get('/import')
            ->assertOk()
            ->assertSee('Import Data')
            ->assertSee('Download import templates');

        Livewire::actingAs($admin)
            ->test(ImportIndex::class)
            ->assertSee('Import Data');
    }

    public function test_admin_can_download_debit_template(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('import.template', 'debit'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_confirm_import_is_blocked_when_errors_exist_and_skip_disabled(): void
    {
        Storage::fake('local');
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $path = storage_path('framework/testing/hexagro-import-invalid-debit.xlsx');
        $this->createInvalidDebitWorkbook($path);

        Livewire::actingAs($admin)
            ->test(ExcelImportModal::class)
            ->call('open', 'debit')
            ->set('workbook', UploadedFile::fake()->createWithContent('invalid.xlsx', (string) file_get_contents($path)))
            ->call('preview')
            ->assertSet('skipErrors', false)
            ->assertSet('step', 2)
            ->tap(function ($component): void {
                $this->assertGreaterThan(0, $component->instance()->errorCount());
            })
            ->call('confirmImport')
            ->assertSet('show', true);
    }

    public function test_outstanding_import_updates_existing_sale_on_reimport(): void
    {
        $path = storage_path('framework/testing/hexagro-outstanding-reimport.xlsx');
        $this->createOutstandingWorkbook($path, 10000);

        app(ExcelImportService::class)->import($path, dryRun: false, only: ['outstanding']);

        $this->assertSame('10000.00', Sale::query()->where('customer_name', 'New Customer Ltd')->value('total_invoiced'));

        $this->createOutstandingWorkbook($path, 25000);

        app(ExcelImportService::class)->import($path, dryRun: false, only: ['outstanding']);

        $this->assertSame('25000.00', Sale::query()->where('customer_name', 'New Customer Ltd')->value('total_invoiced'));
    }

    private function uploadedFixture(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'hexagro-import-modal.xlsx',
            (string) file_get_contents($this->fixturePath),
        );
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
        ]);

        $credit = $spreadsheet->createSheet();
        $credit->setTitle('Credit');
        $credit->fromArray([
            ['Date', 'Cost Center', 'Type', 'Received To', 'Description', 'Amount'],
            ['2026-07-26', 'Chips Unit', 'Sales', 'Union Bank - Current', 'Thara Substrates', 50000],
        ]);

        $outstanding = $spreadsheet->createSheet();
        $outstanding->setTitle('Outstanding');
        $outstanding->fromArray([
            ['Outstanding payments (unpaid — informational, not part of spend or settlement)'],
            [],
            ['Item / Party', 'Cost Center', 'Amount (₹)', 'Notes'],
            ['Sakthi Mariamman', 'Washing Unit', 484690, ''],
        ]);

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }

    private function createInvalidDebitWorkbook(string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Debit');
        $sheet->fromArray([
            ['Date', 'Cost Center', 'Type', 'Account', 'Paid Through', 'Description', 'Total Amount (₹)'],
            ['2026-06-15', 'Unknown Unit', 'Expense', 'Fuel Expense', 'Shareholder - Jagadeesan', 'Invalid row', 1000],
        ]);

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }

    private function createInvalidOutstandingWorkbook(string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Outstanding');
        $sheet->fromArray([
            ['Outstanding payments'],
            [],
            ['Item / Party', 'Cost Center', 'Type', 'Amount (₹)', 'Notes'],
            ['Unknown Party XYZ', 'Fibre Unit', 'Receivable', 1000, ''],
        ]);

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }

    private function createOutstandingWorkbook(string $path, int $amount): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Outstanding');
        $sheet->fromArray([
            ['Outstanding payments'],
            [],
            ['Item / Party', 'Cost Center', 'Type', 'Amount (₹)', 'Notes'],
            ['New Customer Ltd', 'Fibre Unit', 'Receivable', $amount, ''],
        ]);

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }
}
