<?php

namespace App\Services\Dto;

use App\Models\CostCenter;

readonly class LedgerRow
{
    public function __construct(
        public string $txnDate,
        public ?CostCenter $costCenter,
        public string $particulars,
        public string $debit,
        public string $credit,
        public string $runningBalance,
        public string $sourceTable,
        public int $sourceId,
    ) {}
}
