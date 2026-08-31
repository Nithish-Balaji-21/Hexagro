<?php

namespace App\Services\Import;

use App\Models\Transfer;

class TransferSheetImporter
{
    public function __construct(private ExcelLookup $lookup) {}

    /**
     * @param  list<array<string, mixed>>  $debitRows
     * @param  list<array<string, mixed>>  $creditRows
     */
    public function preview(array $debitRows, array $creditRows): ImportPreviewResult
    {
        $previewRows = [];

        $debitTransfers = $this->collectTransferRows($debitRows, 'paid_through');
        $creditTransfers = $this->collectTransferRows($creditRows, 'received_to');

        $pairedCreditKeys = [];

        foreach ($debitTransfers as $debitIndex => $debit) {
            $matchIndex = $this->findMatchingCreditIndex($debit, $creditTransfers, $pairedCreditKeys);

            if ($matchIndex === null) {
                $previewRows[] = new ImportPreviewRow(
                    rowNumber: $debitIndex + 2,
                    date: $debit['date'],
                    costCenter: $debit['cost_center'],
                    detail: "Unpaired debit transfer on {$debit['date']} ({$debit['cost_center']}) from {$debit['entity']} for {$debit['amount']}",
                    amount: $debit['amount'],
                    valid: false,
                    error: "Unpaired debit transfer: from {$debit['entity']} for {$debit['amount']}. Missing matching credit entry.",
                );
                continue;
            }

            $pairedCreditKeys[] = $matchIndex;
            $credit = $creditTransfers[$matchIndex];

            $previewRows[] = new ImportPreviewRow(
                rowNumber: $debitIndex + 2,
                date: $debit['date'],
                costCenter: $debit['cost_center'],
                detail: "{$debit['entity']} → {$credit['entity']}",
                amount: $debit['amount'],
                valid: true,
            );
        }

        foreach ($creditTransfers as $index => $credit) {
            if (in_array($index, $pairedCreditKeys, true)) {
                continue;
            }

            $previewRows[] = new ImportPreviewRow(
                rowNumber: $index + 2,
                date: $credit['date'],
                costCenter: $credit['cost_center'],
                detail: "Unpaired credit transfer on {$credit['date']} ({$credit['cost_center']}) to {$credit['entity']} for {$credit['amount']}",
                amount: $credit['amount'],
                valid: false,
                error: "Unpaired credit transfer: to {$credit['entity']} for {$credit['amount']}. Missing matching debit entry.",
            );
        }

        return new ImportPreviewResult(sheet: 'Transfers', rows: $previewRows);
    }

    /**
     * @param  list<array<string, mixed>>  $debitRows
     * @param  list<array<string, mixed>>  $creditRows
     */
    public function import(array $debitRows, array $creditRows, bool $dryRun = false, ?int $importRunId = null): ImportSheetResult
    {
        $result = new ImportSheetResult(sheet: 'Transfers');

        $debitTransfers = $this->collectTransferRows($debitRows, 'paid_through');
        $creditTransfers = $this->collectTransferRows($creditRows, 'received_to');

        $pairedCreditKeys = [];

        foreach ($debitTransfers as $debitIndex => $debit) {
            $matchIndex = $this->findMatchingCreditIndex($debit, $creditTransfers, $pairedCreditKeys);

            if ($matchIndex === null) {
                $result = new ImportSheetResult(
                    sheet: $result->sheet,
                    imported: $result->imported,
                    created: $result->created,
                    skipped: $result->skipped,
                    errors: $result->errors + 1,
                    messages: [...$result->messages, "Unpaired debit transfer on {$debit['date']} ({$debit['cost_center']}) for {$debit['amount']}"],
                );

                continue;
            }

            $pairedCreditKeys[] = $matchIndex;
            $credit = $creditTransfers[$matchIndex];

            try {
                $attributes = [
                    'txn_date' => $debit['date'],
                    'cost_center_id' => $this->lookup->costCenterId($debit['cost_center']),
                    'from_entity_id' => $this->lookup->entityId($debit['entity']),
                    'to_entity_id' => $this->lookup->entityId($credit['entity']),
                    'note' => $debit['description'] ?: $credit['description'] ?: null,
                    'amount' => $debit['amount'],
                    'created_by' => $this->lookup->adminUserId(),
                ];

                $created = 0;

                if (! $dryRun) {
                    $record = Transfer::query()->firstOrCreate(
                        [
                            'txn_date' => $attributes['txn_date'],
                            'cost_center_id' => $attributes['cost_center_id'],
                            'from_entity_id' => $attributes['from_entity_id'],
                            'to_entity_id' => $attributes['to_entity_id'],
                            'amount' => $attributes['amount'],
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
                    messages: [...$result->messages, "Debit transfer row {$debitIndex}: {$exception->getMessage()}"],
                );
            }
        }

        foreach ($creditTransfers as $index => $credit) {
            if (in_array($index, $pairedCreditKeys, true)) {
                continue;
            }

            $result = new ImportSheetResult(
                sheet: $result->sheet,
                imported: $result->imported,
                created: $result->created,
                skipped: $result->skipped,
                errors: $result->errors + 1,
                messages: [...$result->messages, "Unpaired credit transfer on {$credit['date']} ({$credit['cost_center']}) to {$credit['entity']} for {$credit['amount']}"],
            );
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{date: string, cost_center: string, entity: string, description: ?string, amount: string}>
     */
    private function collectTransferRows(array $rows, string $entityKey): array
    {
        $transfers = [];

        foreach ($rows as $row) {
            $type = trim((string) ($row['type'] ?? ''));

            if ($type === '' || ! $this->lookup->isTransferFund($type)) {
                continue;
            }

            $transfers[] = [
                'date' => $this->lookup->parseDate($row['date'] ?? null),
                'cost_center' => trim((string) ($row['cost_center'] ?? '')),
                'entity' => trim((string) ($row[$entityKey] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'amount' => $this->lookup->parseAmount($row['amount'] ?? null),
            ];
        }

        return $transfers;
    }

    /**
     * @param  list<array{date: string, cost_center: string, entity: string, description: ?string, amount: string}>  $creditTransfers
     * @param  list<int>  $pairedCreditKeys
     */
    private function findMatchingCreditIndex(array $debit, array $creditTransfers, array $pairedCreditKeys): ?int
    {
        foreach ($creditTransfers as $index => $credit) {
            if (in_array($index, $pairedCreditKeys, true)) {
                continue;
            }

            if ($credit['date'] === $debit['date']
                && $credit['cost_center'] === $debit['cost_center']
                && $credit['amount'] === $debit['amount']) {
                return $index;
            }
        }

        return null;
    }
}
