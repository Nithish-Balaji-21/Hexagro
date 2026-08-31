<?php

namespace App\Services\Import;

use App\Models\CreditTransaction;

class CreditSheetImporter
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

            if ($this->lookup->isTransferFund($type)) {
                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $rowNumber,
                    date: $this->lookup->parseDate($row['date'] ?? null) ?: (string) ($row['date'] ?? ''),
                    costCenter: (string) ($row['cost_center'] ?? ''),
                    detail: (string) ($row['received_to'] ?? ''),
                    amount: (string) ($row['amount'] ?? ''),
                    valid: true,
                    skipped: true,
                    skipReason: "Transfer Fund — Skipped here as it is imported via the Transfers tab.",
                );
                continue;
            }

            try {
                $attributes = $this->validateRow($row);

                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $rowNumber,
                    date: $attributes['txn_date'],
                    costCenter: (string) ($row['cost_center'] ?? ''),
                    detail: (string) ($row['received_to'] ?? ''),
                    amount: (string) ($row['amount'] ?? ''),
                    valid: true,
                );
            } catch (\Throwable $exception) {
                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $rowNumber,
                    date: (string) ($row['date'] ?? ''),
                    costCenter: (string) ($row['cost_center'] ?? ''),
                    detail: (string) ($row['received_to'] ?? ''),
                    amount: (string) ($row['amount'] ?? ''),
                    valid: false,
                    error: $exception->getMessage(),
                );
            }
        }

        return new ImportPreviewResult(sheet: 'Credit', rows: $previewRows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function import(array $rows, bool $dryRun = false, ?int $importRunId = null): ImportSheetResult
    {
        $result = new ImportSheetResult(sheet: 'Credit');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $type = trim((string) ($row['type'] ?? ''));

            if ($this->lookup->isTransferFund($type)) {
                continue;
            }

            try {
                $attributes = $this->validateRow($row);

                $created = 0;

                if (! $dryRun) {
                    $record = CreditTransaction::query()->firstOrCreate(
                        [
                            'txn_date' => $attributes['txn_date'],
                            'cost_center_id' => $attributes['cost_center_id'],
                            'received_to_entity_id' => $attributes['received_to_entity_id'],
                            'amount' => $attributes['amount'],
                            'description' => $attributes['description'],
                        ],
                        array_merge($attributes, ['import_run_id' => $importRunId]),
                    );

                    if ($record->wasRecentlyCreated) {
                        $created = 1;
                    }
                }

                $result = new ImportSheetResult(
                    sheet: $result->sheet,
                    imported: $result->imported + 1,
                    created: $result->created + $created,
                    skipped: $result->skipped,
                    errors: $result->errors,
                    messages: $result->messages,
                );
            } catch (\Throwable $exception) {
                $result = new ImportSheetResult(
                    sheet: $result->sheet,
                    imported: $result->imported,
                    created: $result->created,
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

        if ($type === '') {
            throw new \InvalidArgumentException('Type is required (cannot be empty).');
        }

        return [
            'txn_date' => $this->lookup->parseDate($row['date'] ?? null),
            'cost_center_id' => $this->lookup->costCenterId((string) ($row['cost_center'] ?? '')),
            'credit_type' => $this->lookup->creditType($type),
            'received_to_entity_id' => $this->lookup->entityId((string) ($row['received_to'] ?? '')),
            'description' => trim((string) ($row['description'] ?? '')) ?: null,
            'amount' => $this->lookup->parseAmount($row['amount'] ?? null),
            'created_by' => $this->lookup->adminUserId(),
        ];
    }
}
