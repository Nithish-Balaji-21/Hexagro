<?php

namespace App\Services\Dto;

use App\Models\CostCenter;

readonly class MonthlySpendCell
{
    public function __construct(
        public string $monthKey,
        public string $monthLabel,
        public CostCenter $costCenter,
        public string $expenses,
        public string $rawMaterials,
        public string $total,
    ) {}
}
