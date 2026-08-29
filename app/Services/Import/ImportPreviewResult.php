<?php

namespace App\Services\Import;

readonly class ImportPreviewResult
{
    /**
     * @param  list<ImportPreviewRow>  $rows
     */
    public function __construct(
        public string $sheet,
        public array $rows,
    ) {}

    public function validCount(): int
    {
        return count(array_filter($this->rows, fn (ImportPreviewRow $row): bool => $row->valid));
    }

    public function errorCount(): int
    {
        return count(array_filter($this->rows, fn (ImportPreviewRow $row): bool => ! $row->valid));
    }
}
