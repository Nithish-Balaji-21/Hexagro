<?php

namespace App\Services\Import;

use App\Models\DebitTransaction;

class DebitSheetImporter
{
    public function __construct(private ExcelLookup $lookup) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function preview(array $rows): ImportPreviewResult
    {
        $previewRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $type = trim((string) ($row['type'] ?? ''));

            if ($type === '' || $this->lookup->isTransferFund($type)) {
                continue;
            }

            try {
                $this->validateRow($row);

                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $rowNumber,
                    date: (string) ($row['date'] ?? ''),
                    costCenter: (string) ($row['cost_center'] ?? ''),
                    detail: (string) ($row['paid_through'] ?? ''),
                    amount: (string) ($row['amount'] ?? ''),
                    valid: true,
                );
            } catch (\Throwable $exception) {
                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $rowNumber,
                    date: (string) ($row['date'] ?? ''),
                    costCenter: (string) ($row['cost_center'] ?? ''),
                    detail: (string) ($row['paid_through'] ?? ''),
                    amount: (string) ($row['amount'] ?? ''),
                    valid: false,
                    error: $exception->getMessage(),
                );
            }
        }

        return new ImportPreviewResult(sheet: 'Debit', rows: $previewRows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function import(array $rows, bool $dryRun = false): ImportSheetResult
    {
        $result = new ImportSheetResult(sheet: 'Debit');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $type = trim((string) ($row['type'] ?? ''));

                if ($type === '') {
                    $result = new ImportSheetResult(
                        sheet: $result->sheet,
                        imported: $result->imported,
                        skipped: $result->skipped + 1,
                        errors: $result->errors,
                        messages: $result->messages,
                    );

                    continue;
                }

                if ($this->lookup->isTransferFund($type)) {
                    $result = new ImportSheetResult(
                        sheet: $result->sheet,
                        imported: $result->imported,
                        skipped: $result->skipped + 1,
                        errors: $result->errors,
                        messages: $result->messages,
                    );

                    continue;
                }

                $attributes = $this->validateRow($row);

                if (! $dryRun) {
                    DebitTransaction::query()->firstOrCreate(
                        [
                            'txn_date' => $attributes['txn_date'],
                            'cost_center_id' => $attributes['cost_center_id'],
                            'paid_through_entity_id' => $attributes['paid_through_entity_id'],
                            'amount' => $attributes['amount'],
                            'description' => $attributes['description'],
                        ],
                        $attributes,
                    );
                }

                $result = new ImportSheetResult(
                    sheet: $result->sheet,
                    imported: $result->imported + 1,
                    skipped: $result->skipped,
                    errors: $result->errors,
                    messages: $result->messages,
                );
            } catch (\Throwable $exception) {
                $result = new ImportSheetResult(
                    sheet: $result->sheet,
                    imported: $result->imported,
                    skipped: $result->skipped,
                    errors: $result->errors + 1,
                    messages: [...$result->messages, "Row {$rowNumber}: {$exception->getMessage()}"],
                );
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function validateRow(array $row): array
    {
        $type = trim((string) ($row['type'] ?? ''));

        $attributes = [
            'txn_date' => $this->lookup->parseDate($row['date'] ?? null),
            'cost_center_id' => $this->lookup->costCenterId((string) ($row['cost_center'] ?? '')),
            'category' => $this->lookup->debitCategory($type),
            'account' => trim((string) ($row['account'] ?? '')),
            'paid_through_entity_id' => $this->lookup->entityId((string) ($row['paid_through'] ?? '')),
            'description' => trim((string) ($row['description'] ?? '')) ?: null,
            'amount' => $this->lookup->parseAmount($row['amount'] ?? null),
            'created_by' => $this->lookup->adminUserId(),
        ];

        if ($attributes['account'] === '') {
            throw new \InvalidArgumentException('Account is required.');
        }

        return $attributes;
    }
}
