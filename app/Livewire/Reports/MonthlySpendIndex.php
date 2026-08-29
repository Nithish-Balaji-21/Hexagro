<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Services\MonthlySpendService;
use App\Support\FiscalYear;
use App\Support\Money;
use App\Support\UnitScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'monthlySpend', 'pageTitle' => 'Monthly Spend'])]
#[Title('Monthly Spend')]
class MonthlySpendIndex extends Component
{
    use WithUnitScopeRefresh;

    public string $unitFilter = '';

    public string $monthFilter = '';

    public function render(MonthlySpendService $monthlySpendService, UnitScope $unitScope)
    {
        $unitIds = $this->scopedUnitIds();

        if ($this->unitFilter !== '') {
            $unitIds = array_values(array_intersect($unitIds, [(int) $this->unitFilter]));
        }

        $cells = $monthlySpendService->grid($unitIds);

        if ($this->monthFilter !== '') {
            $cells = $cells->filter(fn ($cell): bool => $cell->monthKey === $this->monthFilter);
        }

        $totalExpenses = $cells->reduce(fn (string $c, $cell): string => Money::add($c, $cell->expenses), Money::zero());
        $totalRaw = $cells->reduce(fn (string $c, $cell): string => Money::add($c, $cell->rawMaterials), Money::zero());
        $totalAll = $cells->reduce(fn (string $c, $cell): string => Money::add($c, $cell->total), Money::zero());

        $chartLabels = FiscalYear::months();
        $chartData = collect($chartLabels)->map(function (array $month) use ($cells): float {
            return (float) $cells->where('monthKey', $month['key'])->sum(fn ($cell): float => (float) $cell->total);
        })->all();

        return view('livewire.reports.monthly-spend-index', [
            'cells' => $cells,
            'totalExpenses' => $totalExpenses,
            'totalRaw' => $totalRaw,
            'totalAll' => $totalAll,
            'months' => FiscalYear::months(),
            'scopedUnits' => $this->scopedUnits(),
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
            'chartLabels' => collect($chartLabels)->pluck('label')->map(fn (string $l): string => explode(' ', $l)[0])->all(),
            'chartData' => $chartData,
        ]);
    }
}
