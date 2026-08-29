<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelWorkbook
{
    private Spreadsheet $spreadsheet;

    public function __construct(string $path)
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("Excel file not found: {$path}");
        }

        $this->spreadsheet = IOFactory::load($path);
    }

    public function sheet(string $name): Worksheet
    {
        $sheet = $this->spreadsheet->getSheetByName($name);

        if ($sheet === null) {
            $sheet = $this->spreadsheet->getSheet(0);
        }

        return $sheet;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rowsWithHeaders(string $sheetName, int $headerRow = 1): array
    {
        $sheet = $this->sheet($sheetName);
        $headers = $this->headerMap($sheet, $headerRow);
        $rows = [];

        for ($row = $headerRow + 1; $row <= $sheet->getHighestRow(); $row++) {
            $record = [];
            $hasValue = false;

            foreach ($headers as $column => $header) {
                $value = $sheet->getCell([$column, $row])->getValue();
                $record[$header] = $value;

                if ($value !== null && $value !== '') {
                    $hasValue = true;
                }
            }

            if (! $hasValue) {
                continue;
            }

            if ($this->isSummaryRow($record)) {
                continue;
            }

            $rows[] = $record;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function headerMap(Worksheet $sheet, int $headerRow): array
    {
        $headers = [];

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $value = $sheet->getCell([$column, $headerRow])->getValue();

            if ($value === null || $value === '') {
                continue;
            }

            $headers[$column] = $this->normalizeHeader((string) $value);
        }

        return $headers;
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = str_replace([' (₹)', '₹'], '', $normalized);

        return match ($normalized) {
            'date' => 'date',
            'cost center' => 'cost_center',
            'type' => 'type',
            'account' => 'account',
            'paid through' => 'paid_through',
            'received to' => 'received_to',
            'description' => 'description',
            'total amount' => 'amount',
            'amount' => 'amount',
            'item / party', 'item/party' => 'party',
            'notes' => 'notes',
            'month' => 'month',
            'invoiced' => 'invoiced',
            'received' => 'received',
            default => str_replace([' ', '/'], '_', $normalized),
        };
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function isSummaryRow(array $record): bool
    {
        $party = trim((string) ($record['party'] ?? ''));

        return in_array(mb_strtolower($party), ['total', 'totals'], true);
    }
}
