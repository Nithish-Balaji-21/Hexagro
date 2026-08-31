<?php

namespace App\Services\Import;

use App\Models\CostCenter;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportTemplateService
{
    /**
     * @return list<string>
     */
    public function kinds(): array
    {
        return ['debit', 'credit', 'outstanding'];
    }

    public function download(string $kind): StreamedResponse
    {
        if (! in_array($kind, $this->kinds(), true)) {
            abort(404);
        }

        $spreadsheet = match ($kind) {
            'debit' => $this->debitWorkbook(),
            'credit' => $this->creditWorkbook(),
            default => $this->outstandingWorkbook(),
        };

        $filename = "hexagro-{$kind}-import-template.xlsx";

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function debitWorkbook(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $instructions = $spreadsheet->getActiveSheet();
        $instructions->setTitle('Instructions');
        $this->writeInstructions($instructions, [
            'Upload this sheet as a Debit import or as part of a full workbook.',
            'Required columns: Date, Cost Center, Type, Account, Paid Through, Total Amount.',
            'Type must be Expense or Raw Materials. Transfer Fund rows are skipped.',
            'Date format: YYYY-MM-DD or Excel date. Amount must be positive.',
            'Do not modify the header row on the Data sheet.',
        ]);

        $data = $spreadsheet->createSheet();
        $data->setTitle('Debit');
        $headers = ['Date', 'Cost Center', 'Type', 'Account', 'Paid Through', 'Description', 'Total Amount (₹)'];
        $data->fromArray($headers, null, 'A1');
        $data->fromArray([
            now()->toDateString(),
            $this->sampleCostCenter(),
            'Expense',
            'Fuel Expense',
            'Shareholder - Jagadeesan',
            'Example debit row',
            1000,
        ], null, 'A2');
        $this->styleHeaderRow($data, count($headers));
        $data->freezePane('A2');
        $this->applyListValidation($data, 'C', 'Expense,Raw Materials');
        $this->applyListValidation($data, 'B', $this->costCenterList());

        return $spreadsheet;
    }

    private function creditWorkbook(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $instructions = $spreadsheet->getActiveSheet();
        $instructions->setTitle('Instructions');
        $this->writeInstructions($instructions, [
            'Upload this sheet as a Credit import or as part of a full workbook.',
            'Required columns: Date, Cost Center, Type, Received To, Amount.',
            'Sales-type credits feed the dashboard Credit → Sales card.',
            'Transfer Fund rows are paired separately and skipped here.',
        ]);

        $data = $spreadsheet->createSheet();
        $data->setTitle('Credit');
        $headers = ['Date', 'Cost Center', 'Type', 'Received To', 'Description', 'Amount'];
        $data->fromArray($headers, null, 'A1');
        $data->fromArray([
            now()->toDateString(),
            $this->sampleCostCenter(),
            'Sales',
            'Shareholder - Jagadeesan',
            'Example sales credit',
            5000,
        ], null, 'A2');
        $this->styleHeaderRow($data, count($headers));
        $data->freezePane('A2');
        $this->applyListValidation($data, 'C', 'Sales,Vendor Return,Employee Return,Other Credit');
        $this->applyListValidation($data, 'B', $this->costCenterList());

        return $spreadsheet;
    }

    private function outstandingWorkbook(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $instructions = $spreadsheet->getActiveSheet();
        $instructions->setTitle('Instructions');
        $this->writeInstructions($instructions, [
            'Outstanding rows update customer receivables (Sales page) or vendor payables (Purchases page).',
            'Headers must start on row 3. Rows 1–2 are reserved for title/notes.',
            'Type is required when the party is not already known to the system.',
            'Use Receivable for customer balances and Payable for vendor balances.',
        ]);

        $data = $spreadsheet->createSheet();
        $data->setTitle('Outstanding');
        $data->setCellValue('A1', 'Hexagro Outstanding Import');
        $data->setCellValue('A2', 'Enter rows below. Do not change the header row.');
        $headers = ['Item / Party', 'Cost Center', 'Type', 'Amount (₹)', 'Notes'];
        $data->fromArray($headers, null, 'A3');
        $data->fromArray([
            'Example Customer',
            $this->sampleCostCenter(),
            'Receivable',
            25000,
            'Example receivable balance',
        ], null, 'A4');
        $this->styleHeaderRow($data, count($headers), 3);
        $data->freezePane('A4');
        $this->applyListValidation($data, 'C', 'Receivable,Payable', 4, 500);
        $this->applyListValidation($data, 'B', $this->costCenterList(), 4, 500);

        return $spreadsheet;
    }

    /**
     * @param  list<string>  $lines
     */
    private function writeInstructions(Worksheet $sheet, array $lines): void
    {
        $sheet->setCellValue('A1', 'Hexagro Import Template');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 3;
        foreach ($lines as $line) {
            $sheet->setCellValue("A{$row}", $line);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(90);
        $sheet->getStyle('A3:A'.($row - 1))->getAlignment()->setWrapText(true);
    }

    private function styleHeaderRow(Worksheet $sheet, int $columns, int $row = 1): void
    {
        $range = 'A'.$row.':'.chr(64 + $columns).$row;
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8F5F3');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function applyListValidation(
        Worksheet $sheet,
        string $column,
        string $list,
        int $startRow = 2,
        int $endRow = 200,
    ): void {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"'.$list.'"');
        }
    }

    private function sampleCostCenter(): string
    {
        return CostCenter::query()->orderBy('name')->value('name') ?? 'Fibre Unit';
    }

    private function costCenterList(): string
    {
        return CostCenter::query()->orderBy('name')->pluck('name')->implode(',');
    }
}
