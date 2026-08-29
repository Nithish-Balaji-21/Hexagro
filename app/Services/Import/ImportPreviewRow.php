<?php

namespace App\Services\Import;

readonly class ImportPreviewRow
{
    public function __construct(
        public int $rowNumber,
        public string $date,
        public string $costCenter,
        public string $detail,
        public string $amount,
        public bool $valid,
        public ?string $error = null,
    ) {}
}
