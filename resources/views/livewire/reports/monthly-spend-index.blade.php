<div>
    <x-hex.page-head title="Monthly Spend" subtitle="Calculated automatically from Debit transactions" />
    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" />

    <div class="filter-bar">
        @if ($scopedUnits->count() > 1)
            <select wire:model.live="unitFilter">
                <option value="">All selected units</option>
                @foreach ($scopedUnits as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        @endif
        <select wire:model.live="monthFilter">
            <option value="">All months</option>
            @foreach ($months as $month)
                <option value="{{ $month['key'] }}">{{ $month['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="kpi-grid mb-5" style="grid-template-columns: repeat(3, 1fr);">
        <x-hex.kpi-card label="Total Expenses" :value="\App\Support\Inr::format($totalExpenses, 2)" />
        <x-hex.kpi-card label="Total Raw Materials" :value="\App\Support\Inr::format($totalRaw, 2)" />
        <x-hex.kpi-card label="Total Spend" :value="\App\Support\Inr::format($totalAll, 2)" />
    </div>

    <div class="card mb-4" wire:key="ms-chart-{{ $chartRefreshKey }}">
        <div class="card-head"><h3>Monthly Spend Trend</h3></div>
        <div class="chart-wrap" id="chartMSContainer" data-chart='@json(['labels' => $chartLabels, 'data' => $chartData])'>
            <div class="chart-canvas-box" wire:ignore>
                <canvas id="chartMS"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-scroll">
            @if ($cells->count())
                <table class="data-table">
                    <thead><tr><th>Month</th><th>Cost Center</th><th class="num">Expenses</th><th class="num">Raw Materials</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach ($cells as $cell)
                            <tr wire:key="ms-{{ $cell->monthKey }}-{{ $cell->costCenter->id }}" @if((float)$cell->total == 0) style="opacity:.5" @endif>
                                <td class="mono">{{ $cell->monthLabel }}</td>
                                <td><x-hex.tag :unit="$cell->costCenter->name" /></td>
                                <td class="num amt"><x-hex.money :amount="$cell->expenses" :decimals="2" /></td>
                                <td class="num amt"><x-hex.money :amount="$cell->rawMaterials" :decimals="2" /></td>
                                <td class="num amt">
                                    @if ((float) $cell->total == 0)
                                        <span class="hint">No spend logged</span>
                                    @else
                                        <x-hex.money :amount="$cell->total" :decimals="2" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-hex.empty-state title="No spend logged" description="Try a different unit or month." />
            @endif
        </div>
    </div>
</div>

@script
<script>
    window.renderMSChart = function() {
        const container = document.getElementById('chartMSContainer');
        const canvas = document.getElementById('chartMS');
        if (!container || !canvas || typeof Chart === 'undefined') return;
        const payload = JSON.parse(container.dataset.chart || '{}');
        if (window.__msChart) window.__msChart.destroy();
        window.__msChart = new Chart(canvas, {
            type: 'bar',
            data: { labels: payload.labels || [], datasets: [{ label: 'Total spend', data: payload.data || [], backgroundColor: '#0B5D52', borderRadius: 5 }] },
            options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { display: false } } },
        });
    };
    window.renderMSChart();
    document.removeEventListener('livewire:navigated', window.renderMSChart);
    document.addEventListener('livewire:navigated', window.renderMSChart);
    Livewire.hook('morphed', () => {
        if (document.getElementById('chartMSContainer')) {
            window.renderMSChart();
        }
    });
    Livewire.on('chart-data-updated', () => {
        if (document.getElementById('chartMSContainer')) {
            window.renderMSChart();
        }
    });
</script>
@endscript
