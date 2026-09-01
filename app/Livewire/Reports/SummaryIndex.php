<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithImportRefresh;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\CostCenter;
use App\Services\Dto\EntityFundingRow;
use App\Services\FundingBreakdownService;
use App\Support\Money;
use App\Support\UnitScope;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'summary', 'pageTitle' => 'Summary'])]
#[Title('Summary')]
class SummaryIndex extends Component
{
    use AuthorizesRequests;
    use WithImportRefresh;
    use WithUnitScopeRefresh;

    /** cost center id or 'overall' */
    public string $selectedTab = '';

    public function mount(UnitScope $unitScope): void
    {
        $first = $unitScope->selectedUnits()->first();

        if ($first) {
            $this->selectedTab = $unitScope->isAllSelected()
                ? 'overall'
                : (string) $first->id;
        }
    }

    protected function syncUnitFilters(): void
    {
        $unitScope = app(UnitScope::class);
        $unitIds = $this->scopedUnitIds();
        $allSelected = $unitScope->isAllSelected();

        if ($allSelected) {
            if (! in_array($this->selectedTab, ['overall', ...array_map('strval', $unitIds)], true)) {
                $this->selectedTab = 'overall';
            }
        } elseif ($this->selectedTab === 'overall' || ! in_array((int) $this->selectedTab, $unitIds, true)) {
            $this->selectedTab = (string) ($unitIds[0] ?? '');
        }
    }

    public function setTab(string $tab): void
    {
        $this->selectedTab = $tab;
    }

    public function render(
        FundingBreakdownService $fundingBreakdownService,
        UnitScope $unitScope,
    ) {
        $unitIds = $this->scopedUnitIds();
        $allSelected = $unitScope->isAllSelected();
        $isOverall = $this->selectedTab === 'overall';

        $fundingRows = $isOverall
            ? $this->aggregateFunding($fundingBreakdownService, $unitIds)
            : $fundingBreakdownService->forCostCenter(CostCenter::query()->findOrFail((int) $this->selectedTab));

        $chartData = $fundingRows
            ->filter(fn (EntityFundingRow $row): bool => Money::cmp($row->entityTotal, '0') !== 0)
            ->map(fn (EntityFundingRow $row): array => [
                'label' => $row->entity->short_name,
                'value' => (float) $row->entityTotal,
            ])->values()->all();

        return view('livewire.reports.summary-index', [
            'scopedUnits' => CostCenter::query()->whereIn('id', $unitIds)->orderBy('name')->get(),
            'allSelected' => $allSelected,
            'isOverall' => $isOverall,
            'fundingRows' => $fundingRows,
            'scopeLabel' => $unitScope->scopeLabel(),
            'chartData' => $chartData,
            'importRefreshVersion' => $this->importRefreshVersion,
            'chartRefreshKey' => md5(json_encode($chartData).$this->unitScopeVersion.$this->importRefreshVersion.$this->selectedTab),
        ]);
    }

    /**
     * @param  list<int>  $unitIds
     * @return Collection<int, EntityFundingRow>
     */
    private function aggregateFunding(FundingBreakdownService $service, array $unitIds): Collection
    {
        $merged = [];

        foreach ($unitIds as $unitId) {
            $costCenter = CostCenter::query()->findOrFail($unitId);

            foreach ($service->forCostCenter($costCenter) as $row) {
                $id = $row->entity->id;

                if (! isset($merged[$id])) {
                    $merged[$id] = $row;

                    continue;
                }

                $existing = $merged[$id];
                $merged[$id] = new EntityFundingRow(
                    entity: $row->entity,
                    expenses: Money::add($existing->expenses, $row->expenses),
                    rawMaterials: Money::add($existing->rawMaterials, $row->rawMaterials),
                    otherDebits: Money::add($existing->otherDebits, $row->otherDebits),
                    credits: Money::add($existing->credits, $row->credits),
                    entityTotal: Money::add($existing->entityTotal, $row->entityTotal),
                );
            }
        }

        return collect(array_values($merged));
    }
}
