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
    public function import(array $debitRows, array $creditRows, bool $dryRun = false): ImportSheetResult
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

                if (! $dryRun) {
                    Transfer::query()->firstOrCreate(
                        [
                            'txn_date' => $attributes['txn_date'],
                            'cost_center_id' => $attributes['cost_center_id'],
                            'from_entity_id' => $attributes['from_entity_id'],
                            'to_entity_id' => $attributes['to_entity_id'],
                            'amount' => $attributes['amount'],
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
