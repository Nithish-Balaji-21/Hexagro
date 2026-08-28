<div>
    <x-hex.page-head title="Ledger Book" subtitle="Running Dr/Cr ledger for each funding entity" />
    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" />

    <div class="tabs mb-4 flex-wrap">
        @foreach ($entities as $entity)
            <button type="button" wire:click="setEntity({{ $entity->id }})" class="tab-btn {{ $selectedEntityId === (string) $entity->id ? 'active' : '' }}">{{ $entity->short_name }}</button>
        @endforeach
    </div>

    <div class="kpi-grid mb-5" style="grid-template-columns: repeat(3, 1fr);">
        <x-hex.kpi-card label="Total Debit (Dr)" :value="\App\Support\Inr::format($totalDebit, 2)" />
        <x-hex.kpi-card label="Total Credit (Cr)" :value="\App\Support\Inr::format($totalCredit, 2)" />
        <x-hex.kpi-card label="Closing Balance" :value="\App\Support\Inr::format(abs((float)$closing), 2)" />
    </div>

    <div class="card">
        <div class="card-head"><h3>Ledger entries</h3></div>
        <div class="table-scroll">
            @if ($rows->count())
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Unit</th><th>Particulars</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="el-{{ $row->sourceTable }}-{{ $row->sourceId }}">
                                <td class="mono">{{ \App\Support\Inr::formatDate($row->txnDate) }}</td>
                                <td><x-hex.tag :unit="$row->costCenter->name" /></td>
                                <td class="desc-cell">{{ $row->particulars }}</td>
                                <td class="num amt">@if((float)$row->debit > 0)<x-hex.money :amount="$row->debit" :decimals="2" />@else — @endif</td>
                                <td class="num amt">@if((float)$row->credit > 0)<x-hex.money :amount="$row->credit" :decimals="2" />@else — @endif</td>
                                <td class="num amt font-semibold"><x-hex.money :amount="abs((float)$row->runningBalance)" :decimals="2" /> {{ (float)$row->runningBalance >= 0 ? 'Cr' : 'Dr' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-hex.empty-state title="No entries yet" description="Nothing has moved through this entity for the selected units." />
            @endif
        </div>
    </div>
</div>
