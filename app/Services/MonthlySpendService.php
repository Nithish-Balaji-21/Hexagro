<?php

namespace App\Services;

use App\Enums\DebitCategory;
use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Services\Dto\MonthlySpendCell;
use App\Support\FiscalYear;
use App\Support\Money;
use Illuminate\Support\Collection;

class MonthlySpendService
{
    /**
     * FY month × cost center spend grid from posted debits.
     *
     * @param  list<int>|null  $costCenterIds
     * @return Collection<int, MonthlySpendCell>
     */
    public function grid(?array $costCenterIds = null, ?int $fiscalYearStart = null): Collection
    {
        $months = FiscalYear::months($fiscalYearStart);
        $first = $months[0]['start'];
        $last = $months[11]['end'];

        $costCenters = CostCenter::query()
            ->when($costCenterIds !== null, fn ($query) => $query->whereIn('id', $costCenterIds))
            ->orderBy('id')
            ->get();

        $aggregates = DebitTransaction::query()
            ->selectRaw("DATE_FORMAT(txn_date, '%Y-%m') as month_key")
            ->selectRaw('cost_center_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN category = ? THEN amount ELSE 0 END), 0) as expenses', [DebitCategory::Expense->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN category = ? THEN amount ELSE 0 END), 0) as raw_materials', [DebitCategory::RawMaterials->value])
            ->whereBetween('txn_date', [$first->toDateString(), $last->toDateString()])
            ->when($costCenterIds !== null, fn ($query) => $query->whereIn('cost_center_id', $costCenterIds))
            ->groupByRaw("DATE_FORMAT(txn_date, '%Y-%m'), cost_center_id")
            ->get()
            ->keyBy(fn ($row): string => $row->month_key.'|'.$row->cost_center_id);

        $cells = collect();

        foreach ($months as $month) {
            foreach ($costCenters as $costCenter) {
                $row = $aggregates->get($month['key'].'|'.$costCenter->id);
                $expenses = Money::of($row->expenses ?? 0);
                $rawMaterials = Money::of($row->raw_materials ?? 0);

                $cells->push(new MonthlySpendCell(
                    monthKey: $month['key'],
                    monthLabel: $month['label'],
                    costCenter: $costCenter,
                    expenses: $expenses,
                    rawMaterials: $rawMaterials,
                    total: Money::add($expenses, $rawMaterials),
                ));
            }
        }

        return $cells;
    }
}
