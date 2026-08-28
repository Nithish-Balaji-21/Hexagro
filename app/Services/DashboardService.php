<?php

namespace App\Services;

use App\Enums\CreditType;
use App\Enums\DebitCategory;
use App\Models\CostCenter;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Dto\DashboardSummary;
use App\Services\Dto\ShareholderBar;
use App\Support\DateRange;
use App\Support\Money;

class DashboardService
{
    public function __construct(
        private BankingService $bankingService,
        private SettlementService $settlementService,
    ) {}

    /**
     * @param  list<int>  $costCenterIds
     */
    public function summary(DateRange $range, array $costCenterIds): DashboardSummary
    {
        $debits = DebitTransaction::query()
            ->whereIn('cost_center_id', $costCenterIds)
            ->whereBetween('txn_date', [$range->from, $range->to])
            ->selectRaw('COALESCE(SUM(CASE WHEN category = ? THEN amount ELSE 0 END), 0) as expenses', [DebitCategory::Expense->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN category = ? THEN amount ELSE 0 END), 0) as raw_materials', [DebitCategory::RawMaterials->value])
            ->first();

        $credits = CreditTransaction::query()
            ->whereIn('cost_center_id', $costCenterIds)
            ->whereBetween('txn_date', [$range->from, $range->to])
            ->selectRaw('COALESCE(SUM(CASE WHEN credit_type = ? THEN amount ELSE 0 END), 0) as sales', [CreditType::Sales->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN credit_type != ? THEN amount ELSE 0 END), 0) as others', [CreditType::Sales->value])
            ->first();

        $banking = $this->bankingService->current();

        $payables = Purchase::query()
            ->whereIn('cost_center_id', $costCenterIds)
            ->whereNotNull('total_billed')
            ->sum('balance');

        $receivables = Sale::query()
            ->whereIn('cost_center_id', $costCenterIds)
            ->sum('balance');

        return new DashboardSummary(
            debitExpense: Money::of($debits->expenses ?? 0),
            debitRaw: Money::of($debits->raw_materials ?? 0),
            creditSales: Money::of($credits->sales ?? 0),
            creditOthers: Money::of($credits->others ?? 0),
            bankCurrent: $banking?->snapshot->current_balance,
            bankCcUtilised: $banking?->snapshot->cc_utilised,
            bankTermLoan: $banking?->snapshot->term_loan,
            payables: Money::of($payables ?? 0),
            receivables: Money::of($receivables ?? 0),
        );
    }

    /**
     * @param  list<int>  $costCenterIds
     * @return list<ShareholderBar>
     */
    public function shareholderBars(array $costCenterIds): array
    {
        $labels = [
            'jagadeesan' => 'Jagadeesan',
            'jagadeshwaran' => 'Jagadeshwaran',
            'vellingiri' => 'Vellingiri',
            'vikas' => 'Vikas',
        ];

        $totals = array_fill_keys(array_values($labels), [
            'contribution' => Money::zero(),
            'fairShare' => Money::zero(),
        ]);

        foreach ($costCenterIds as $costCenterId) {
            $settlement = $this->settlementService->forCostCenter(
                CostCenter::query()->findOrFail($costCenterId),
            );

            foreach ($settlement->partners as $partner) {
                $key = $partner->entity->configKey();
                $label = $labels[$key] ?? null;

                if ($label === null) {
                    continue;
                }

                $totals[$label]['contribution'] = Money::add($totals[$label]['contribution'], $partner->contribution);
                $totals[$label]['fairShare'] = Money::add($totals[$label]['fairShare'], $partner->fairShare);
            }
        }

        return collect(array_values($labels))->map(fn (string $name): ShareholderBar => new ShareholderBar(
            name: $name,
            contribution: $totals[$name]['contribution'],
            fairShare: $totals[$name]['fairShare'],
        ))->all();
    }
}
