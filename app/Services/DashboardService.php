<?php

namespace App\Services;

use App\Enums\CreditType;
use App\Enums\DebitCategory;
use App\Models\CostCenter;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Entity;
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

        $banking = $this->bankingService->asOf($range->to ?? now()->toDateString());

        $payables = $this->outstandingPayables($costCenterIds, $range->to ?? now()->toDateString());
        $receivables = $this->outstandingReceivables($costCenterIds, $range->to ?? now()->toDateString());

        return new DashboardSummary(
            debitExpense: Money::of($debits->expenses ?? 0),
            debitRaw: Money::of($debits->raw_materials ?? 0),
            creditSales: Money::of($credits->sales ?? 0),
            creditOthers: Money::of($credits->others ?? 0),
            bankCurrent: $banking?->snapshot->current_balance,
            bankCcLimit: $banking?->snapshot->cc_limit,
            bankCcUtilised: $banking?->snapshot->cc_utilised,
            bankTlLimit: $banking?->snapshot->tl_limit,
            bankTermLoan: $banking?->snapshot->term_loan,
            payables: Money::of($payables),
            receivables: Money::of($receivables),
        );
    }

    /**
     * @param  list<int>  $costCenterIds
     * @return list<ShareholderBar>
     */
    public function shareholderBars(array $costCenterIds, DateRange $range): array
    {
        $fibreUnitId = (int) CostCenter::query()
            ->where('name', config('hexagro.fibre_unit_name'))
            ->value('id');
        $includeVikas = in_array($fibreUnitId, $costCenterIds, true);

        $labels = Entity::query()
            ->shareholders()
            ->active()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Entity $entity): array => [
                $entity->configKey() => $entity->short_name ?? $entity->name,
            ])
            ->filter(fn (string $label, ?string $key): bool => $key !== null && $label !== '')
            ->when(! $includeVikas, fn ($collection) => $collection->except(['vikas']))
            ->all();

        $totals = array_fill_keys(array_values($labels), [
            'contribution' => Money::zero(),
            'fairShare' => Money::zero(),
        ]);

        foreach ($costCenterIds as $costCenterId) {
            $settlement = $this->settlementService->forCostCenter(
                CostCenter::query()->findOrFail($costCenterId),
                $range,
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

    /**
     * @param  list<int>  $costCenterIds
     */
    private function outstandingPayables(array $costCenterIds, string $asOfDate): string
    {
        $total = Money::zero();

        foreach ($costCenterIds as $costCenterId) {
            $latestTxnDate = Purchase::query()
                ->where('cost_center_id', $costCenterId)
                ->whereNotNull('txn_date')
                ->where('txn_date', '<=', $asOfDate)
                ->max('txn_date');

            if ($latestTxnDate === null) {
                continue;
            }

            $total = Money::add(
                $total,
                Purchase::query()
                    ->where('cost_center_id', $costCenterId)
                    ->where('txn_date', $latestTxnDate)
                    ->whereNotNull('total_billed')
                    ->sum('balance'),
            );
        }

        return $total;
    }

    /**
     * @param  list<int>  $costCenterIds
     */
    private function outstandingReceivables(array $costCenterIds, string $asOfDate): string
    {
        $total = Money::zero();

        foreach ($costCenterIds as $costCenterId) {
            $latestTxnDate = Sale::query()
                ->where('cost_center_id', $costCenterId)
                ->whereNotNull('txn_date')
                ->where('txn_date', '<=', $asOfDate)
                ->max('txn_date');

            if ($latestTxnDate === null) {
                continue;
            }

            $total = Money::add(
                $total,
                Sale::query()
                    ->where('cost_center_id', $costCenterId)
                    ->where('txn_date', $latestTxnDate)
                    ->sum('balance'),
            );
        }

        return $total;
    }
}
