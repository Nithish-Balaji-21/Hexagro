<?php

namespace App\Services\Dto;

use App\Support\Money;

class DashboardSummary
{
    public function __construct(
        public string $debitExpense,
        public string $debitRaw,
        public string $creditSales,
        public string $creditOthers,
        public ?string $bankCurrent,
        public ?string $bankCcLimit,
        public ?string $bankCcUtilised,
        public ?string $bankTlLimit,
        public ?string $bankTermLoan,
        public string $payables,
        public string $receivables,
    ) {}

    public function debitTotal(): string
    {
        return Money::add($this->debitExpense, $this->debitRaw);
    }

    public function creditTotal(): string
    {
        return Money::add($this->creditSales, $this->creditOthers);
    }
}
