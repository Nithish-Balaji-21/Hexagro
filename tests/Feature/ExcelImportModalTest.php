<?php

namespace Tests\Feature;

use App\Livewire\Import\ExcelImportModal;
use App\Livewire\Import\ImportIndex;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\User;
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
            ->assertSee('Import Data');

        Livewire::actingAs($admin)
            ->test(ImportIndex::class)
            ->assertSee('Import Data');
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
}
