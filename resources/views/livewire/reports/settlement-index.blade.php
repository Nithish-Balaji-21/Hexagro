<div>
    <x-hex.page-head title="Summary & Settlement" subtitle="Funding breakdown and shareholder reconciliation" />

    <x-hex.explain-card title="How to read this page">
        Settlement compares each shareholder's effective contribution against their fair share based on ownership.
        Positive outstanding means they should receive money back; negative means they owe.
    </x-hex.explain-card>

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
                                <td class="num amt"><x-hex.money :amount="$row->expenses" :decimals="2" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->rawMaterials" :decimals="2" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->otherDebits" :decimals="2" /></td>
                                <td class="num amt rec"><x-hex.money :amount="$row->credits" :decimals="2" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->entityTotal" :decimals="2" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-pad" wire:key="settlement-chart-{{ $chartRefreshKey }}" id="chartSummaryContainer" data-chart='@json($chartData)'>
            <div class="card-head" style="border:none;padding:0 0 12px"><h3>Funding Mix</h3></div>
            <div class="chart-canvas-box" wire:ignore>
                <canvas id="chartSummary"></canvas>
            </div>
        </div>
    </div>

    @if ($isOverall && $overall)
        <div class="card mb-4">
            <div class="card-head"><h3>Overall Net Position</h3></div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Partner</th><th class="num">Overall Net</th><th class="num">Adjustment</th><th class="num">Adjusted Net</th><th class="num">Outstanding</th><th>Position</th></tr></thead>
                    <tbody>
                        @foreach ($overall as $row)
                            <tr wire:key="ov-{{ $row->entity->id }}">
                                <td>{{ $row->entity->short_name }}</td>
                                <td class="num amt"><x-hex.money :amount="$row->overallNet" :decimals="2" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->adjustment" :decimals="2" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->adjustedNet" :decimals="2" /></td>
                                <td class="num amt"><x-hex.money :amount="$row->outstanding" :decimals="2" /></td>
                                <td><x-hex.net-badge :amount="$row->outstanding" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($unitSettlement)
        <div class="partner-cards mb-4">
            @foreach ($unitSettlement->partners as $partner)
                <div class="partner-card" wire:key="p-{{ $partner->entity->id }}">
                    <div class="pname">{{ $partner->entity->short_name }} <span>{{ number_format((float) $partner->sharePct * 100, 1) }}% share</span></div>
                    <div class="pc-row"><span>Paid directly</span><b><x-hex.money :amount="$partner->paidDirectly" :decimals="2" /></b></div>
                    <div class="pc-row"><span>Alam share</span><b><x-hex.money :amount="$partner->alamShare" :decimals="2" /></b></div>
                    <div class="pc-row"><span>UBI share</span><b><x-hex.money :amount="$partner->ubiShare" :decimals="2" /></b></div>
                    <div class="pc-row font-semibold border-t border-[var(--border)] mt-1 pt-2"><span>Contribution</span><b><x-hex.money :amount="$partner->contribution" :decimals="2" /></b></div>
                    <div class="pc-row font-semibold"><span>Fair share</span><b><x-hex.money :amount="$partner->fairShare" :decimals="2" /></b></div>
                    <x-hex.net-badge :amount="$partner->outstanding" />
                </div>
            @endforeach
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-head"><h3>Suggested Transfers to Settle</h3></div>
        <div class="card-pad">
            @if (count($suggestedTransfers) === 0)
                <p class="hint">All shareholders are within tolerance — nothing left to transfer for this scope.</p>
            @else
                @foreach ($suggestedTransfers as $i => $transfer)
                    <div class="transfer-suggest-row" wire:key="st-{{ $i }}">
                        <span><b>{{ $transfer->from->short_name }}</b> → <b>{{ $transfer->to->short_name }}</b></span>
                        <span class="font-mono"><x-hex.money :amount="$transfer->amount" :decimals="2" /></span>
                        @can('create', App\Models\SettlementLedgerEntry::class)
                            <button type="button" wire:click="openLedgerForm({{ $transfer->from->id }}, {{ $transfer->to->id }}, '{{ $transfer->amount }}')" class="hex-btn hex-btn-sm">Log payment</button>
                        @endcan
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-head">
            <h3>Settlement Ledger</h3>
            @can('create', App\Models\SettlementLedgerEntry::class)
                <button type="button" wire:click="openLedgerForm" class="hex-btn hex-btn-sm hex-btn-primary"><x-hex.icon name="plus" /> Log payment</button>
            @endcan
        </div>
        <div class="table-scroll">
            @if ($ledgerEntries->count())
                <table class="data-table">
                    <thead><tr><th>Date</th>@if($isOverall)<th>Unit</th>@endif<th>From</th><th>To</th><th class="num">Amount</th><th>Note</th>@can('create', App\Models\SettlementLedgerEntry::class)<th class="num">Actions</th>@endcan</tr></thead>
                    <tbody>
                        @foreach ($ledgerEntries as $entry)
                            <tr wire:key="lg-{{ $entry->id }}">
                                <td class="mono">{{ $entry->txn_date->format('d M Y') }}</td>
                                @if ($isOverall)<td>{{ $entry->unit_scope }}</td>@endif
                                <td>{{ $entry->fromEntity->short_name }}</td>
                                <td>{{ $entry->toEntity->short_name }}</td>
                                <td class="num amt"><x-hex.money :amount="$entry->amount" :decimals="2" /></td>
                                <td class="desc-cell">{{ $entry->note }}</td>
                                @can('delete', $entry)
                                    <td class="num"><button type="button" wire:click="deleteLedgerEntry({{ $entry->id }})" wire:confirm="Remove entry?" class="tbl-icon-btn danger"><x-hex.icon name="trash" /></button></td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-hex.empty-state title="No payments logged yet" description="Log shareholder-to-shareholder settlement payments here." />
            @endif
        </div>
    </div>

    @if ($showLedgerForm)
        <div class="modal-overlay show"><div class="modal">
            <div class="modal-head"><h3>Log Settlement Payment</h3><button type="button" wire:click="closeLedgerForm" class="tbl-icon-btn"><x-hex.icon name="x" /></button></div>
            <form wire:submit="saveLedgerEntry" class="modal-body"><div class="form-grid">
                <label><span>Date</span><input type="date" wire:model="ledgerDate"></label>
                <label><span>From</span><select wire:model="ledgerFromId">@foreach($shareholders as $s)<option value="{{ $s->id }}">{{ $s->short_name }}</option>@endforeach</select></label>
                <label><span>To</span><select wire:model="ledgerToId">@foreach($shareholders as $s)<option value="{{ $s->id }}">{{ $s->short_name }}</option>@endforeach</select></label>
                <label><span>Amount</span><input type="number" step="0.01" min="0.01" wire:model="ledgerAmount"></label>
                <label class="span-2"><span>Note</span><input type="text" wire:model="ledgerNote"></label>
            </div><div class="modal-foot"><button type="button" wire:click="closeLedgerForm" class="hex-btn">Cancel</button><button type="submit" class="hex-btn hex-btn-primary">Save</button></div></form>
        </div></div>
    @endif
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
