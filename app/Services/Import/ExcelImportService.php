<?php

namespace App\Services\Import;

use App\Models\Transfer;
use App\Services\LedgerRebuildService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExcelImportService
{
    public function __construct(
        private ExcelLookup $lookup,
        private DebitSheetImporter $debitImporter,
        private CreditSheetImporter $creditImporter,
        private TransferSheetImporter $transferImporter,
        private OutstandingSheetImporter $outstandingImporter,
        private LedgerRebuildService $ledgerRebuildService,
        private ImportRunService $importRunService,
    ) {}

    /**
     * @param  list<string>|null  $only
     * @return list<ImportSheetResult>
     */
    public function import(
        string $path,
        bool $dryRun = false,
        ?array $only = null,
        ?string $filename = null,
        ?int $userId = null,
    ): array {
        $workbook = new ExcelWorkbook($path);
        $sheets = $only ?? ['debit', 'credit', 'transfers', 'outstanding'];
        $results = [];

        $debitRows = in_array('debit', $sheets, true) || in_array('transfers', $sheets, true)
            ? $workbook->rowsWithHeaders('Debit')
            : [];
        $creditRows = in_array('credit', $sheets, true) || in_array('transfers', $sheets, true)
            ? $workbook->rowsWithHeaders('Credit')
            : [];
        $outstandingRows = in_array('outstanding', $sheets, true)
            ? $workbook->rowsWithHeaders('Outstanding', headerRow: 3)
            : [];

        $importFilename = $filename ?? basename($path);
        $importUserId = $userId ?? $this->lookup->adminUserId();

        $run = function () use (
            $dryRun,
            $sheets,
            $debitRows,
            $creditRows,
            $outstandingRows,
            $importFilename,
            $importUserId,
            &$results,
        ): void {
            if (in_array('debit', $sheets, true)) {
                $importRun = $this->startImportRun('debit', $dryRun, $importFilename, $importUserId);
                $result = $this->debitImporter->import($debitRows, $dryRun, $importRun?->id);
                $this->finishImportRun($importRun, $result, $dryRun);
                $results[] = $result;
            }

            if (in_array('credit', $sheets, true)) {
                $importRun = $this->startImportRun('credit', $dryRun, $importFilename, $importUserId);
                $result = $this->creditImporter->import($creditRows, $dryRun, $importRun?->id);
                $this->finishImportRun($importRun, $result, $dryRun);
                $results[] = $result;
            }

            if (in_array('transfers', $sheets, true)) {
                $importRun = $this->startImportRun('transfers', $dryRun, $importFilename, $importUserId);
                $result = $this->transferImporter->import($debitRows, $creditRows, $dryRun, $importRun?->id);
                $this->finishImportRun($importRun, $result, $dryRun);
                $results[] = $result;
            }

            if (in_array('outstanding', $sheets, true)) {
                $results[] = $this->outstandingImporter->import($outstandingRows, $dryRun);
            }
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction(function () use ($run, $sheets): void {
                Model::withoutEvents($run);

                if ($this->importsLedgerSources($sheets)) {
                    $this->ledgerRebuildService->rebuildAll();
                }
            });
        }

        return $results;
    }

    /**
     * @param  list<string>|null  $only
     * @return list<ImportPreviewResult>
     */
    public function preview(string $path, ?array $only = null): array
    {
        $workbook = new ExcelWorkbook($path);
        $sheets = $only ?? ['debit', 'credit', 'transfers', 'outstanding'];
        $results = [];

        $needsDebit = in_array('debit', $sheets, true) || in_array('transfers', $sheets, true);
        $needsCredit = in_array('credit', $sheets, true) || in_array('transfers', $sheets, true);

        $debitRows = $needsDebit ? $workbook->rowsWithHeaders('Debit') : [];
        $creditRows = $needsCredit ? $workbook->rowsWithHeaders('Credit') : [];
        $outstandingRows = in_array('outstanding', $sheets, true)
            ? $workbook->rowsWithHeaders('Outstanding', headerRow: 3)
            : [];

        if (in_array('debit', $sheets, true)) {
            $results[] = $this->debitImporter->preview($debitRows);
        }

        if (in_array('credit', $sheets, true)) {
            $results[] = $this->creditImporter->preview($creditRows);
        }

        if (in_array('transfers', $sheets, true)) {
            $transferResult = $this->transferImporter->import($debitRows, $creditRows, dryRun: true);
            $results[] = $this->summaryPreview('Transfers', $transferResult);
        }

        if (in_array('outstanding', $sheets, true)) {
            $results[] = $this->outstandingImporter->preview($outstandingRows);
        }

        return $results;
    }

    private function summaryPreview(string $sheet, ImportSheetResult $result): ImportPreviewResult
    {
        $rows = [];

        if ($result->imported > 0) {
            $rows[] = new ImportPreviewRow(
                rowNumber: 0,
                date: '',
                costCenter: '',
                detail: "{$result->imported} row(s) ready to import",
                amount: '',
                valid: true,
            );
        }

        foreach ($result->messages as $message) {
            $rows[] = new ImportPreviewRow(
                rowNumber: 0,
                date: '',
                costCenter: '',
                detail: $message,
                amount: '',
                valid: false,
                error: $message,
            );
        }

        return new ImportPreviewResult(sheet: $sheet, rows: $rows);
    }

    /**
     * @param  list<string>  $sheets
     */
    private function importsLedgerSources(array $sheets): bool
    {
        return count(array_intersect($sheets, ['debit', 'credit', 'transfers'])) > 0;
    }

    private function startImportRun(string $kind, bool $dryRun, string $filename, int $userId): ?\App\Models\ImportRun
    {
        if ($dryRun) {
            return null;
        }

        return $this->importRunService->start($kind, $filename, $userId);
    }

    private function finishImportRun(?\App\Models\ImportRun $importRun, ImportSheetResult $result, bool $dryRun): void
    {
        if ($dryRun || $importRun === null) {
            return;
        }

        if ($result->created === 0) {
            $importRun->delete();

            return;
        }

        $this->importRunService->finish($importRun, $result->created);
    }
}
