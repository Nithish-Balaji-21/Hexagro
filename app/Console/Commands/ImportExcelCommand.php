<?php

namespace App\Console\Commands;

use App\Services\Import\ExcelImportService;
use App\Services\Import\ImportSheetResult;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hexagro:import-excel {file : Path to the Hexagro Excel workbook} {--dry-run : Parse and validate without writing to the database} {--only= : Comma-separated sheets to import: debit,credit,transfers,outstanding}')]
#[Description('Import debit, credit, transfer, and outstanding rows from the Hexagro Excel workbook')]
class ImportExcelCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ExcelImportService $importService): int
    {
        $path = $this->resolvePath((string) $this->argument('file'));
        $dryRun = (bool) $this->option('dry-run');
        $only = $this->parseOnlyOption();

        if ($dryRun) {
            $this->info('Dry run — no database changes will be made.');
        }

        $this->info("Importing from: {$path}");

        $results = $importService->import($path, $dryRun, $only);

        $this->newLine();
        $this->table(
            ['Sheet', 'Imported', 'Skipped', 'Errors'],
            collect($results)->map(fn (ImportSheetResult $result): array => [
                $result->sheet,
                $result->imported,
                $result->skipped,
                $result->errors,
            ])->all(),
        );

        $messages = collect($results)->flatMap(fn (ImportSheetResult $result): array => $result->messages);

        if ($messages->isNotEmpty()) {
            $this->newLine();
            $this->warn('Messages:');

            foreach ($messages as $message) {
                $this->line("  - {$message}");
            }
        }

        $hasErrors = collect($results)->contains(fn (ImportSheetResult $result): bool => $result->errors > 0);

        if ($hasErrors) {
            $this->newLine();
            $this->error('Import completed with errors.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run completed successfully.' : 'Import completed successfully.');

        return self::SUCCESS;
    }

    private function resolvePath(string $file): string
    {
        if (is_file($file)) {
            return $file;
        }

        $storagePath = storage_path('app/'.$file);

        if (is_file($storagePath)) {
            return $storagePath;
        }

        $basePath = base_path($file);

        if (is_file($basePath)) {
            return $basePath;
        }

        throw new \InvalidArgumentException("Excel file not found: {$file}");
    }

    /**
     * @return list<string>|null
     */
    private function parseOnlyOption(): ?array
    {
        $only = $this->option('only');

        if ($only === null || $only === '') {
            return null;
        }

        $sheets = array_values(array_filter(array_map(
            fn (string $sheet): string => mb_strtolower(trim($sheet)),
            explode(',', (string) $only),
        )));

        $allowed = ['debit', 'credit', 'transfers', 'outstanding'];

        foreach ($sheets as $sheet) {
            if (! in_array($sheet, $allowed, true)) {
                throw new \InvalidArgumentException("Invalid sheet in --only: {$sheet}. Allowed: ".implode(', ', $allowed));
            }
        }

        return $sheets;
    }
}
