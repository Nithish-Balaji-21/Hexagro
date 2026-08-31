<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithImportRefresh;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Services\DashboardService;
use App\Services\Dto\ShareholderBar;
use App\Support\UnitScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'dashboard', 'pageTitle' => 'Dashboard'])]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use WithDateRange;
    use WithImportRefresh;
    use WithUnitScopeRefresh;

    public function render(DashboardService $dashboardService, UnitScope $unitScope)
    {
        $unitIds = $unitScope->selectedUnitIds();
        $range = $this->dateRange();
        $summary = $dashboardService->summary($range, $unitIds);
        $shareholderBars = $dashboardService->shareholderBars($unitIds);
        $chartData = $this->chartPayload($shareholderBars);

        return view('livewire.dashboard', [
            'summary' => $summary,
            'shareholderBars' => $shareholderBars,
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
            'chartData' => $chartData,
            'unitScopeVersion' => $this->unitScopeVersion,
            'importRefreshVersion' => $this->importRefreshVersion,
            'chartRefreshKey' => md5(json_encode($chartData).$this->unitScopeVersion.$this->importRefreshVersion.$this->rangePreset.$this->rangeFrom.$this->rangeTo),
        ]);
    }

    /**
     * @param  list<ShareholderBar>  $bars
     * @return array<string, mixed>
     */
    private function chartPayload(array $bars): array
    {
        return [
            'labels' => collect($bars)->pluck('name')->all(),
            'contributions' => collect($bars)->map(fn (ShareholderBar $bar): float => (float) $bar->contribution)->all(),
            'fairShares' => collect($bars)->map(fn (ShareholderBar $bar): float => (float) $bar->fairShare)->all(),
        ];
    }
}
