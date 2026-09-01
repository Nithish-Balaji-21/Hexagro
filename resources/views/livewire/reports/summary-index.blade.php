<div>
    <x-hex.page-head title="Summary" subtitle="Funding breakdown and paid through analysis" />

    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" action="see another unit or overall position" />

    <div class="tabs mb-4">
        @foreach ($scopedUnits as $unit)
            <button type="button" wire:click="setTab('{{ $unit->id }}')" class="tab-btn {{ $selectedTab === (string) $unit->id ? 'active' : '' }}">{{ $unit->name }}</button>
        @endforeach
        @if ($allSelected)
            <button type="button" wire:click="setTab('overall')" class="tab-btn {{ $selectedTab === 'overall' ? 'active' : '' }}">Overall Position</button>
        @endif
    </div>

    <div class="chart-grid mb-4">
        <div class="card">
            <div class="card-head"><h3>Paid Through Breakdown</h3><span class="hint">FY-to-date</span></div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Paid Through</th><th class="num">Expenses</th><th class="num">Raw Materials</th><th class="num">Other Debits</th><th class="num">Credits</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach ($fundingRows as $row)
                            <tr wire:key="fb-{{ $row->entity->id }}">
                                <td>{{ $row->entity->name }}</td>
                                <td class="num amt"><x-hex.money :amount="$row->expenses" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->rawMaterials" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->otherDebits" /></td>
                                <td class="num amt rec"><x-hex.money :amount="$row->credits" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->entityTotal" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-pad" wire:key="summary-chart-{{ $chartRefreshKey }}" id="chartSummaryContainer" data-chart='@json($chartData)'>
            <div class="card-head" style="border:none;padding:0 0 12px"><h3>Funding Mix</h3></div>
            <div class="chart-canvas-box" wire:ignore>
                <canvas id="chartSummary"></canvas>
            </div>
        </div>
    </div>
</div>

@script
<script>
    window.renderSummaryChart = function() {
        const container = document.getElementById('chartSummaryContainer');
        const canvas = document.getElementById('chartSummary');
        if (!container || !canvas || typeof Chart === 'undefined') return;
        const rows = JSON.parse(container.dataset.chart || '[]');
        if (window.__summaryChart) window.__summaryChart.destroy();
        if (!rows.length) return;
        window.__summaryChart = new Chart(canvas, {
            type: 'doughnut',
            data: { labels: rows.map(r => r.label), datasets: [{ data: rows.map(r => r.value), backgroundColor: ['#0B5D52','#175CD3','#B54708','#96650F','#7C3AED','#15803D','#334155','#DB2777'] }] },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '58%' },
        });
    };
    window.renderSummaryChart();
    document.removeEventListener('livewire:navigated', window.renderSummaryChart);
    document.addEventListener('livewire:navigated', window.renderSummaryChart);
    Livewire.hook('morphed', () => {
        if (document.getElementById('chartSummaryContainer')) {
            window.renderSummaryChart();
        }
    });
    Livewire.on('chart-data-updated', () => {
        if (document.getElementById('chartSummaryContainer')) {
            window.renderSummaryChart();
        }
    });
</script>
@endscript
