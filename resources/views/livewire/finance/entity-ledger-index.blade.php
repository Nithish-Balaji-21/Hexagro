<div>
    <x-hex.page-head title="Ledger Book" subtitle="Running Dr/Cr ledger for each funding entity">
        <x-slot:actions>
            @if ($selectedEntity)
                <button type="button" wire:click="exportPdf" class="hex-btn hex-btn-primary">
                    <x-hex.icon name="download" />
                    Export PDF
                </button>
            @endif
        </x-slot:actions>
    </x-hex.page-head>

    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" />

    <div class="filter-bar filter-bar-range mb-4">
        <span class="fb-label"><x-hex.icon name="calendar" /> Date range</span>
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

    <div class="card mb-4" style="padding: 6px 14px; background: var(--surface);">
        <div class="tabs" style="border-bottom: none; margin-bottom: 0; flex-wrap: nowrap; overflow-x: auto;">
            @foreach ($entities as $entity)
                <button
                    type="button"
                    wire:click="setEntity({{ $entity->id }})"
                    class="tab-btn {{ $selectedEntityId === (string) $entity->id ? 'active' : '' }}"
                >
                    {{ $entity->short_name }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($selectedEntity)
        <div class="hint mb-3">
            Showing ledger for <b>{{ $selectedEntity->short_name }}</b>
            @if ((float) $openingBalance !== 0.0)
                · Opening balance: <x-hex.money :amount="abs((float) $openingBalance)" /> {{ (float) $openingBalance >= 0 ? 'Cr' : 'Dr' }}
            @endif
        </div>
    @endif

    <div class="kpi-grid mb-5" style="grid-template-columns: repeat(3, 1fr);">
        <x-hex.kpi-card label="Total Debit (Dr)" :value="\App\Support\Inr::format($totalDebit)" />
        <x-hex.kpi-card label="Total Credit (Cr)" :value="\App\Support\Inr::format($totalCredit)" />
        <x-hex.kpi-card label="Closing Balance" :value="\App\Support\Inr::format(abs((float)$closing))" />
    </div>

    <div class="card">
        <div class="card-head"><h3>Ledger entries</h3></div>
        <div class="table-scroll">
            @if ($rows->count() || (float) $openingBalance !== 0.0)
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Unit</th><th>Particulars</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr></thead>
                    <tbody>
                        @if ((float) $openingBalance !== 0.0)
                            <tr class="ledger-opening-row">
                                <td class="mono">—</td>
                                <td>—</td>
                                <td class="desc-cell font-semibold">Opening balance</td>
                                <td class="num amt">—</td>
                                <td class="num amt">—</td>
                                <td class="num amt font-semibold">
                                    <x-hex.money :amount="abs((float) $openingBalance)" />
                                    {{ (float) $openingBalance >= 0 ? 'Cr' : 'Dr' }}
                                </td>
                            </tr>
                        @endif
                        @foreach ($rows as $row)
                            <tr wire:key="el-{{ $row->sourceTable }}-{{ $row->sourceId }}">
                                <td class="mono">{{ \App\Support\Inr::formatDate($row->txnDate) }}</td>
                                <td><x-hex.tag :unit="$row->costCenter->name" /></td>
                                <td class="desc-cell">{{ $row->particulars }}</td>
                                <td class="num amt">@if((float)$row->debit > 0)<x-hex.money :amount="$row->debit" />@else — @endif</td>
                                <td class="num amt">@if((float)$row->credit > 0)<x-hex.money :amount="$row->credit" />@else — @endif</td>
                                <td class="num amt font-semibold"><x-hex.money :amount="abs((float)$row->runningBalance)" /> {{ (float)$row->runningBalance >= 0 ? 'Cr' : 'Dr' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-hex.empty-state title="No entries yet" description="Nothing has moved through this entity for the selected units and date range." />
            @endif
        </div>
    </div>
</div>
