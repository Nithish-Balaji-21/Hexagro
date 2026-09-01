<div>
    <x-hex.page-head
        :title="'Welcome back, '.explode(' ', auth()->user()->name)[0]"
        :subtitle="$allSelected ? 'Here\'s where the business stands today.' : 'Filtered to '.$scopeLabel.'.'"
    />

    <div class="filter-bar filter-bar-range">
        <x-hex.range-picker
            :preset="$rangePreset"
            :from="$rangeFrom"
            :to="$rangeTo"
            :picker-open="$rangePickerOpen"
            :picker-from="$pickerFrom"
            :picker-to="$pickerTo"
            :picker-preset="$pickerPreset"
        />
    </div>

    <div class="dash-card-grid" wire:key="unit-scope-{{ $unitScopeVersion }}-{{ $importRefreshVersion }}">
        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="down" /></div>
                <h3>Debit</h3>
            </div>
            <div class="dmc-row"><span>Raw Material</span><b><x-hex.money :amount="$summary->debitRaw" /></b></div>
            <div class="dmc-row"><span>Expense</span><b><x-hex.money :amount="$summary->debitExpense" /></b></div>
            <div class="dmc-total">Total<b><x-hex.money :amount="$summary->debitTotal()" /></b></div>
        </div>

        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="up" /></div>
                <h3>Credit</h3>
            </div>
            <div class="dmc-row"><span>Sales</span><b class="rec"><x-hex.money :amount="$summary->creditSales" /></b></div>
            <div class="dmc-row"><span>Others</span><b class="rec"><x-hex.money :amount="$summary->creditOthers" /></b></div>
            <div class="dmc-total">Total<b class="rec"><x-hex.money :amount="$summary->creditTotal()" /></b></div>
        </div>

        @if (auth()->user()->configKey() !== 'vikas')
        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="bank" /></div>
                <h3>Banking</h3>
            </div>
            <p class="hint mb-2">{{ $summary->bankAsOfDate ? 'As of '.$summary->bankAsOfDate : 'Latest banking snapshot' }}</p>
            <div class="dmc-row"><span>CA</span><b><x-hex.money :amount="$summary->bankCurrent" /></b></div>
            <div class="dmc-row"><span>CC limit</span><b><x-hex.money :amount="$summary->bankCcLimit" /></b></div>
            <div class="dmc-row"><span>CC utilised</span><b><x-hex.money :amount="$summary->bankCcUtilised" /></b></div>
            <div class="dmc-row"><span>TL limit</span><b><x-hex.money :amount="$summary->bankTlLimit" /></b></div>
            <div class="dmc-row"><span>TL outstanding</span><b><x-hex.money :amount="$summary->bankTermLoan" /></b></div>
            @if (! $allSelected)
                <p class="hint mt-2">Company-wide snapshot — not unit-specific.</p>
            @endif
        </div>
        @endif

        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="clock" /></div>
                <h3>Outstandings</h3>
            </div>
            <div class="dmc-row">
                <span class="dmc-row-label">Payables <x-hex.icon name="outward" class="dmc-row-icon pay" /></span>
                <b class="pay"><x-hex.money :amount="$summary->payables" /></b>
            </div>
            <div class="dmc-row">
                <span class="dmc-row-label">Receivables <x-hex.icon name="inward" class="dmc-row-icon rec" /></span>
                <b class="rec"><x-hex.money :amount="$summary->receivables" /></b>
            </div>
        </div>

        <div class="card card-pad dash-mini-card dash-chart-card" wire:key="share-fair-chart-{{ $chartRefreshKey }}">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="layers" /></div>
                <h3>Shareholder Contribution vs Fair Share</h3>
            </div>
            <p class="hint mb-2">Settlement position for selected units in range</p>
            <div class="chart-wrap" id="chartShareFairContainer" data-chart='@json($chartData)'>
                <div class="chart-canvas-box" wire:ignore>
                    <canvas id="chartShareFair"></canvas>
                </div>
            </div>
            <p class="hint mt-2">Green = contribution · amber = over fair share · hatched = still owed @if (! $allSelected) · {{ $scopeLabel }}@endif</p>
        </div>
    </div>
</div>

@script
<script>
    window.hatchPattern = function(color) {
        const c = document.createElement('canvas');
        c.width = 8;
        c.height = 8;
        const ctx = c.getContext('2d');
        ctx.strokeStyle = color;
        ctx.lineWidth = 1.6;
        [[-2, 10, 2, -2], [2, 10, 6, -2], [6, 10, 10, 2]].forEach(([x1, y1, x2, y2]) => {
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        });
        return ctx.createPattern(c, 'repeat');
    };

    window.formatInr = function(value) {
        const rounded = Math.round(Number(value) || 0);
        const negative = rounded < 0;
        const abs = Math.abs(rounded).toString();
        const lastThree = abs.slice(-3);
        const rest = abs.slice(0, -3);
        const grouped = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + (rest ? ',' : '') + lastThree;
        return (negative ? '−' : '') + '₹' + grouped;
    };

    window.renderShareFairChart = function() {
        const container = document.getElementById('chartShareFairContainer');
        const canvas = document.getElementById('chartShareFair');
        if (!container || !canvas || typeof Chart === 'undefined') return;

        const data = JSON.parse(container.dataset.chart || '{}');
        if (window.__shareFairChart) {
            window.__shareFairChart.destroy();
        }

        const contribs = data.contributions || [];
        const fairs = data.fairShares || [];
        const base = contribs.map((c, i) => Math.min(c, fairs[i] || 0));
        const gap = contribs.map((c, i) => Math.max((fairs[i] || 0) - c, 0));
        const over = contribs.map((c, i) => Math.max(c - (fairs[i] || 0), 0));

        window.__shareFairChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [
                    { label: 'Contribution', data: base, backgroundColor: '#0B5D52', stack: 's', maxBarThickness: 56 },
                    { label: 'Over fair share', data: over, backgroundColor: '#D97706', stack: 's', maxBarThickness: 56 },
                    { label: 'Still owed', data: gap, backgroundColor: window.hatchPattern('#96650F'), borderColor: '#96650F', borderWidth: 1.5, stack: 's', maxBarThickness: 56 },
                ],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${window.formatInr(ctx.raw)}`,
                        },
                    },
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: {
                        stacked: true,
                        grid: { color: '#EEF1F4' },
                        ticks: { callback: (v) => window.formatInr(v) },
                    },
                },
            },
        });
    };

    window.renderShareFairChart();
    document.removeEventListener('livewire:navigated', window.renderShareFairChart);
    document.addEventListener('livewire:navigated', window.renderShareFairChart);
    Livewire.hook('morphed', () => {
        if (document.getElementById('chartShareFairContainer')) {
            window.renderShareFairChart();
        }
    });
    Livewire.on('chart-data-updated', () => {
        if (document.getElementById('chartShareFairContainer')) {
            window.renderShareFairChart();
        }
    });
</script>
@endscript
