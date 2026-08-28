<div>
    <x-hex.page-head
        :title="'Welcome back, '.explode(' ', auth()->user()->name)[0]"
        :subtitle="$allSelected ? 'Here\'s where the business stands today.' : 'Filtered to '.$scopeLabel.'.'"
    >
        <x-slot:actions>
            <a href="{{ route('debit') }}" wire:navigate class="hex-btn hex-btn-primary">
                <x-hex.icon name="down" />
                Go to Debit
            </a>
        </x-slot:actions>
    </x-hex.page-head>

    <div class="filter-bar filter-bar-range">
        <span class="fb-label"><x-hex.icon name="calendar" /> Spend range</span>
        <x-hex.range-picker :preset="$rangePreset" :from="$rangeFrom" :to="$rangeTo" />
    </div>

    <p class="hint mb-4">Debit &amp; Credit cards follow the range above · Banking &amp; Outstandings are current balances.</p>

    <div class="dash-card-grid">
        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="down" /></div>
                <h3>Debit</h3>
            </div>
            <div class="dmc-row"><span>Raw Material</span><b><x-hex.money :amount="$summary->debitRaw" :decimals="2" /></b></div>
            <div class="dmc-row"><span>Expense</span><b><x-hex.money :amount="$summary->debitExpense" :decimals="2" /></b></div>
            <div class="dmc-total">Total<b><x-hex.money :amount="$summary->debitTotal()" :decimals="2" /></b></div>
        </div>

        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="up" /></div>
                <h3>Credit</h3>
            </div>
            <div class="dmc-row"><span>Sales</span><b class="rec"><x-hex.money :amount="$summary->creditSales" :decimals="2" /></b></div>
            <div class="dmc-row"><span>Others</span><b class="rec"><x-hex.money :amount="$summary->creditOthers" :decimals="2" /></b></div>
            <div class="dmc-total">Total<b class="rec"><x-hex.money :amount="$summary->creditTotal()" :decimals="2" /></b></div>
        </div>

        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="bank" /></div>
                <h3>Banking</h3>
            </div>
            <div class="dmc-row"><span>CA</span><b><x-hex.money :amount="$summary->bankCurrent" :decimals="2" /></b></div>
            <div class="dmc-row"><span>CC</span><b><x-hex.money :amount="$summary->bankCcUtilised" :decimals="2" /></b></div>
            <div class="dmc-row"><span>TL</span><b><x-hex.money :amount="$summary->bankTermLoan" :decimals="2" /></b></div>
        </div>

        <div class="card card-pad dash-mini-card">
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="clock" /></div>
                <h3>Outstandings</h3>
            </div>
            <div class="dmc-row"><span>Payables</span><b class="pay"><x-hex.money :amount="$summary->payables" :decimals="2" /></b></div>
            <div class="dmc-row"><span>Receivables</span><b class="rec"><x-hex.money :amount="$summary->receivables" :decimals="2" /></b></div>
        </div>

        <div class="card card-pad dash-mini-card dash-chart-card" wire:ignore>
            <div class="dmc-head">
                <div class="kpi-icon"><x-hex.icon name="layers" /></div>
                <h3>Shareholder Contribution vs Fair Share</h3>
            </div>
            <div class="chart-wrap">
                <div class="chart-canvas-box">
                    <canvas id="chartShareFair" data-chart='@json($chartData)'></canvas>
                </div>
            </div>
            <p class="hint mt-2">Solid = contribution so far · hatched = still owed to reach fair share@if (! $allSelected) · {{ $scopeLabel }}@endif</p>
        </div>
    </div>
</div>

@script
<script>
    function renderShareFairChart() {
        const canvas = document.getElementById('chartShareFair');
        if (!canvas || typeof Chart === 'undefined') return;

        const data = JSON.parse(canvas.dataset.chart || '{}');
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
                    { label: 'Over fair share', data: over, backgroundColor: '#0B5D52', stack: 's', maxBarThickness: 56 },
                    { label: 'Still owed', data: gap, backgroundColor: '#96650F55', borderColor: '#96650F', borderWidth: 1.5, stack: 's', maxBarThickness: 56 },
                ],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: { legend: { display: true, position: 'bottom' } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, grid: { color: '#EEF1F4' } },
                },
            },
        });
    }

    renderShareFairChart();
    document.addEventListener('livewire:navigated', renderShareFairChart);
</script>
@endscript
