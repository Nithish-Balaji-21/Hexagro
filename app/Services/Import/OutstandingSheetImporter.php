<?php

namespace App\Services\Import;

use App\Models\Purchase;
use App\Models\Sale;

class OutstandingSheetImporter
{
    public function __construct(private ExcelLookup $lookup) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function preview(array $rows): ImportPreviewResult
    {
        $previewRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 4;

            try {
                $validated = $this->validateRow($row);

                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $rowNumber,
                    date: $validated['txn_date'],
                    costCenter: (string) ($row['cost_center'] ?? ''),
                    detail: $validated['party'].' ('.$validated['kind'].')',
                    amount: $validated['amount'],
                    valid: true,
                );
            } catch (\Throwable $exception) {
                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $rowNumber,
                    date: (string) ($row['date'] ?? ''),
                    costCenter: (string) ($row['cost_center'] ?? ''),
                    detail: trim((string) ($row['party'] ?? '')),
                    amount: (string) ($row['amount'] ?? ''),
                    valid: false,
                    error: $exception->getMessage(),
                );
            }
        }

        return new ImportPreviewResult(sheet: 'Outstanding', rows: $previewRows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function import(array $rows, bool $dryRun = false): ImportSheetResult
    {
        $result = new ImportSheetResult(sheet: 'Outstanding');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 4;

            try {
                $party = trim((string) ($row['party'] ?? ''));

                if ($party === '') {
                    $result = new ImportSheetResult(
                        sheet: $result->sheet,
                        imported: $result->imported,
                        skipped: $result->skipped + 1,
                        errors: $result->errors,
                        messages: $result->messages,
                    );

                    continue;
                }

                $validated = $this->validateRow($row);
                $asOfDate = now()->toDateString();
                $txnDate = $validated['txn_date'];

                if ($validated['kind'] === 'payable') {
                    if (! $dryRun) {
                        Purchase::query()->updateOrCreate(
                            [
                                'cost_center_id' => $validated['cost_center_id'],
                                'vendor_name' => $validated['party'],
                            ],
                            [
                                'total_billed' => $validated['amount'],
                                'total_paid' => '0.00',
                                'notes' => $validated['notes'],
                                'as_of_date' => $asOfDate,
                                'txn_date' => $txnDate,
                            ],
                        );
                    }
                } else {
                    if (! $dryRun) {
                        Sale::query()->updateOrCreate(
                            [
                                'cost_center_id' => $validated['cost_center_id'],
                                'customer_name' => $validated['party'],
                            ],
                            [
                                'total_invoiced' => $validated['amount'],
                                'total_received' => '0.00',
                                'notes' => $validated['notes'],
                                'as_of_date' => $asOfDate,
                                'txn_date' => $txnDate,
                            ],
                        );
                    }
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
     * @return array{party: string, kind: string, cost_center_id: int, amount: string, notes: ?string, txn_date: string}
     */
    private function validateRow(array $row): array
    {
        $party = trim((string) ($row['party'] ?? ''));
        $type = trim((string) ($row['type'] ?? ''));
        $kind = $this->lookup->outstandingKind($party, $type !== '' ? $type : null);

        if ($kind === null) {
            throw new \InvalidArgumentException(
                "Unknown outstanding party: {$party}. Add a Type column (Receivable/Payable) or use a known party name.",
            );
        }

        $amount = $this->lookup->parseOptionalAmount($row['amount'] ?? null);

        if ($amount === null) {
            throw new \InvalidArgumentException('Amount is required and must be greater than zero.');
        }

        $rawDate = $row['date'] ?? $row['txn_date'] ?? null;
        $txnDate = ($rawDate !== null && trim((string) $rawDate) !== '')
            ? $this->lookup->parseDate($rawDate)
            : now()->toDateString();

        return [
            'party' => $party,
            'kind' => $kind,
            'cost_center_id' => $this->lookup->costCenterId((string) ($row['cost_center'] ?? '')),
            'amount' => $amount,
            'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
            'txn_date' => $txnDate,
        ];
    }
}
