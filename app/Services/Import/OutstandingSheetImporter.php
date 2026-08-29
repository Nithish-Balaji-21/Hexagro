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

                $kind = $this->lookup->outstandingKind($party);

                if ($kind === null) {
                    $result = new ImportSheetResult(
                        sheet: $result->sheet,
                        imported: $result->imported,
                        skipped: $result->skipped + 1,
                        errors: $result->errors + 1,
                        messages: [...$result->messages, "Row {$rowNumber}: Unknown outstanding party kind for {$party}"],
                    );

                    continue;
                }

                $amount = $this->lookup->parseOptionalAmount($row['amount'] ?? null);
                $notes = trim((string) ($row['notes'] ?? '')) ?: null;
                $costCenterId = $this->lookup->costCenterId((string) ($row['cost_center'] ?? ''));

                if ($amount === null) {
                    $result = new ImportSheetResult(
                        sheet: $result->sheet,
                        imported: $result->imported,
                        skipped: $result->skipped + 1,
                        errors: $result->errors,
                        messages: $result->messages,
                    );

                    continue;
                }

                if ($kind === 'payable') {
                    if (! $dryRun) {
                        Purchase::query()->firstOrCreate(
                            [
                                'cost_center_id' => $costCenterId,
                                'vendor_name' => $party,
                            ],
                            [
                                'total_billed' => $amount,
                                'total_paid' => '0.00',
                                'notes' => $notes,
                            ],
                        );
                    }
                } else {
                    if (! $dryRun) {
                        Sale::query()->firstOrCreate(
                            [
                                'cost_center_id' => $costCenterId,
                                'customer_name' => $party,
                            ],
                            [
                                'total_invoiced' => $amount,
                                'total_received' => '0.00',
                                'notes' => $notes,
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
                    skipped: $result->skipped,
                    errors: $result->errors + 1,
                    messages: [...$result->messages, "Row {$rowNumber}: {$exception->getMessage()}"],
                );
            }
        }

        return $result;
    }
}
