<?php

namespace App\Livewire\Import;

use App\Models\DebitTransaction;
use App\Services\Import\ExcelImportService;
use App\Services\Import\ImportPreviewResult;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class ExcelImportModal extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $show = false;

    public string $kind = 'workbook';

    public int $step = 1;

    /** @var list<array{sheet: string, rows: list<array{rowNumber: int, date: string, costCenter: string, detail: string, amount: string, valid: bool, error: ?string}>}> */
    public array $previewResults = [];

    public bool $skipErrors = false;

    public ?string $storedPath = null;

    public $workbook;

    public function mount(bool $showOnLoad = false, string $kind = 'workbook'): void
    {
        if ($showOnLoad && auth()->user()?->isAdmin()) {
            $this->kind = $kind;
            $this->show = true;
        }
    }

    #[On('open-import')]
    public function open(string $kind = 'workbook'): void
    {
        $this->authorize('create', DebitTransaction::class);

        $this->kind = $kind;
        $this->show = true;
        $this->resetImportState();
    }

    public function close(): void
    {
        $this->cleanupStoredFile();
        $this->show = false;
        $this->resetImportState();
    }

    public function updatedWorkbook(): void
    {
        $this->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);
    }

    public function preview(ExcelImportService $importService): void
    {
        $this->authorize('create', DebitTransaction::class);

        $this->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        $this->cleanupStoredFile();
        $this->storedPath = $this->workbook->store('imports', 'local');
        $absolutePath = Storage::disk('local')->path($this->storedPath);

        $this->previewResults = array_map(
            fn (ImportPreviewResult $result): array => [
                'sheet' => $result->sheet,
                'rows' => array_map(
                    fn ($row): array => [
                        'rowNumber' => $row->rowNumber,
                        'date' => $row->date,
                        'costCenter' => $row->costCenter,
                        'detail' => $row->detail,
                        'amount' => $row->amount,
                        'valid' => $row->valid,
                        'error' => $row->error,
                    ],
                    $result->rows,
                ),
            ],
            $importService->preview($absolutePath, $this->sheetsToImport()),
        );
        $this->step = 2;
    }

    public function confirmImport(ExcelImportService $importService): void
    {
        $this->authorize('create', DebitTransaction::class);

        if ($this->storedPath === null) {
            return;
        }

        if ($this->errorCount() > 0 && ! $this->skipErrors) {
            $this->dispatch('toast', message: "Import blocked: {$this->errorCount()} row(s) have validation errors. Fix them or enable skipping error rows.");

            return;
        }

        $absolutePath = Storage::disk('local')->path($this->storedPath);
        $results = $importService->import($absolutePath, dryRun: false, only: $this->sheetsToImport());

        $summary = collect($results)
            ->map(fn ($result): string => "{$result->sheet}: {$result->imported} imported, {$result->skipped} skipped, {$result->errors} errors")
            ->implode(' · ');

        $this->cleanupStoredFile();
        $this->show = false;
        $this->resetImportState();

        $this->dispatch('toast', message: "Import complete. {$summary}");
        $this->dispatch('import-completed');
    }

    public function detailColumnLabel(): string
    {
        return $this->kind === 'credit' ? 'Received To' : 'Paid Through';
    }

    public function modalTitle(): string
    {
        return match ($this->kind) {
            'debit' => 'Import Debit',
            'credit' => 'Import Credit',
            default => 'Import Workbook',
        };
    }

    public function validCount(): int
    {
        return collect($this->previewResults)->sum(
            fn (array $result): int => count(array_filter($result['rows'], fn (array $row): bool => $row['valid'])),
        );
    }

    public function errorCount(): int
    {
        return collect($this->previewResults)->sum(
            fn (array $result): int => count(array_filter($result['rows'], fn (array $row): bool => ! $row['valid'])),
        );
    }

    /**
     * @return list<array{sheet: string, rows: list<array{rowNumber: int, date: string, costCenter: string, detail: string, amount: string, valid: bool, error: ?string}>}>
     */
    public function previewResultsForDisplay(): array
    {
        if ($this->kind === 'workbook') {
            return $this->previewResults;
        }

        return array_values(array_filter(
            $this->previewResults,
            fn (array $result): bool => mb_strtolower($result['sheet']) === mb_strtolower($this->kind),
        ));
    }

    public function render()
    {
        return view('livewire.import.excel-import-modal');
    }

    /**
     * @return list<string>
     */
    private function sheetsToImport(): array
    {
        return match ($this->kind) {
            'debit' => ['debit'],
            'credit' => ['credit'],
            default => ['debit', 'credit', 'transfers', 'outstanding'],
        };
    }

    private function resetImportState(): void
    {
        $this->step = 1;
        $this->previewResults = [];
        $this->skipErrors = false;
        $this->workbook = null;
        $this->storedPath = null;
    }

    private function cleanupStoredFile(): void
    {
        if ($this->storedPath !== null) {
            Storage::disk('local')->delete($this->storedPath);
            $this->storedPath = null;
        }
    }
}
