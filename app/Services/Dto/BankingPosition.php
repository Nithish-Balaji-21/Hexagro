<?php

namespace App\Services\Dto;

use App\Models\BankingSnapshot;

readonly class BankingPosition
{
    public function __construct(
        public BankingSnapshot $snapshot,
        public string $ccAvailable,
        public string $alamUtilised,
        public string $alamPayable,
    ) {}
}
