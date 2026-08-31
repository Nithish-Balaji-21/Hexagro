<div>
    <x-hex.page-head title="Transfers" subtitle="Money moved between bank accounts">
        @can('create', App\Models\Transfer::class)
            <x-slot:actions>
                <a href="{{ route('import', ['kind' => 'transfers']) }}" wire:navigate class="hex-btn icon-only" title="Import history / Revert">
                    <x-hex.icon name="history" />
                </a>
                <button type="button" wire:click="$dispatch('open-import', { kind: 'transfers' })" class="hex-btn">
                    <x-hex.icon name="upload" />
                    Import
                </button>
                <button type="button" wire:click="openCreateForm" class="hex-btn hex-btn-primary">
                    <x-hex.icon name="plus" /> Add Transfer
                </button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>

    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" />
    <div class="unit-tabs-header-bar mb-4">
        <x-hex.unit-tabs :units="$scopedUnits" :active="$unitTab" />
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

    <div class="card mb-4">
        <div class="card-head"><h3>Ledger — Net Position per Entity</h3></div>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Entity</th><th class="num">Net</th></tr></thead>
                <tbody>
                    @foreach ($entityNets as $row)
                        <tr wire:key="net-{{ $row['entity']->id }}">
                            <td>{{ $row['entity']->short_name }}</td>
                            <td class="num amt {{ (float) $row['net'] > 0 ? 'rec' : ((float) $row['net'] < 0 ? 'pay' : '') }}">
                                <x-hex.money :amount="$row['net']" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="filter-bar">
        <select wire:model.live="fromFilter">
            <option value="">Any from</option>
            @foreach ($entities as $entity)
                <option value="{{ $entity->id }}">{{ $entity->short_name }}</option>
            @endforeach
        </select>
        <select wire:model.live="toFilter">
            <option value="">Any to</option>
            @foreach ($entities as $entity)
                <option value="{{ $entity->id }}">{{ $entity->short_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="card">
        <div class="table-scroll">
            @if ($transfers->count())
                <table class="data-table">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('txn_date')" class="sortable">Date</th>
                            <th>Cost Center</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Note</th>
                            <th wire:click="sortBy('amount')" class="sortable num">Amount</th>
                            @can('create', App\Models\Transfer::class)
                                <th class="num">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfers as $transfer)
                            <tr wire:key="tr-{{ $transfer->id }}">
                                <td class="mono">{{ $transfer->txn_date->format('d M Y') }}</td>
                                <td><x-hex.tag :unit="$transfer->costCenter->name" /></td>
                                <td>{{ $transfer->fromEntity->short_name }}</td>
                                <td>{{ $transfer->toEntity->short_name }}</td>
                                <td class="desc-cell">{{ $transfer->note }}</td>
                                <td class="num amt"><x-hex.money :amount="$transfer->amount" /></td>
                                @can('update', $transfer)
                                    <td class="num">
                                        <div class="row-actions">
                                            <button type="button" wire:click="openEditForm({{ $transfer->id }})" class="tbl-icon-btn"><x-hex.icon name="edit" /></button>
                                            <button type="button" wire:click="delete({{ $transfer->id }})" wire:confirm="Delete this transfer?" class="tbl-icon-btn danger"><x-hex.icon name="trash" /></button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-hex.empty-state title="No transfers found" description="Try adjusting your filters." />
            @endif
        </div>
        @if ($transfers->count())
            <div class="table-foot"><span>Total: <b class="font-mono"><x-hex.money :amount="$totalAmount" /></b></span></div>
            <div class="px-4 pb-4">{{ $transfers->links() }}</div>
        @endif
    </div>

@if ($showForm)
    <div class="modal-overlay show">
        <div class="modal">
            <div class="modal-head">
                <h3>{{ $editingId ? 'Edit Transfer' : 'Add Transfer' }}</h3>
                <button type="button" wire:click="closeForm" class="tbl-icon-btn"><x-hex.icon name="x" /></button>
            </div>
            <form wire:submit="save" class="modal-body">
                <div class="form-grid">
                    <label><span>Date</span><input type="date" wire:model="formDate"></label>
                    <label><span>Cost Center</span>
                        <select wire:model="formCostCenterId">
                            @foreach ($scopedUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label><span>From</span>
                        <select wire:model="formFromId">@foreach ($entities as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
                    </label>
                    <label><span>To</span>
                        <select wire:model="formToId">@foreach ($entities as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
                    </label>
                    <label class="span-2"><span>Note</span><input type="text" wire:model="formNote"></label>
                    <label><span>Amount</span><input type="number" step="0.01" min="0.01" wire:model="formAmount"></label>
                </div>
                <div class="modal-foot">
                    <button type="button" wire:click="closeForm" class="hex-btn">Cancel</button>
                    <button type="submit" class="hex-btn hex-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
