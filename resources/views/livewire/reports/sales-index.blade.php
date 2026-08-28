<div>
    <x-hex.page-head title="Sales" subtitle="Customer ledger — invoiced, received and outstanding">
        @can('create', App\Models\Sale::class)
            <x-slot:actions>
                <button type="button" wire:click="openCreateForm" class="hex-btn hex-btn-primary"><x-hex.icon name="plus" /> Add Sale</button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>
    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" />

    <div class="filter-bar">
        @if ($visibleUnits->count() > 1)
            <select wire:model.live="unitFilter"><option value="">All units</option>@foreach($visibleUnits as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
        @endif
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search customer…">
    </div>

    <div class="kpi-grid mb-5" style="grid-template-columns: repeat(3, 1fr);">
        <x-hex.kpi-card label="Total Invoiced" :value="\App\Support\Inr::format($totalInvoiced, 2)" />
        <x-hex.kpi-card label="Total Received" :value="\App\Support\Inr::format($totalReceived, 2)" />
        <x-hex.kpi-card label="Receivables" :value="\App\Support\Inr::format($totalOutstanding, 2)" />
    </div>

    <div class="card">
        <div class="table-scroll">
            @if ($sales->count())
                <table class="data-table">
                    <thead><tr><th>Customer</th><th>Unit</th><th class="num">Invoiced</th><th class="num">Received</th><th class="num">Balance</th><th>Notes</th>@can('create', App\Models\Sale::class)<th class="num">Actions</th>@endcan</tr></thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr wire:key="sa-{{ $sale->id }}">
                                <td>{{ $sale->customer_name }}</td>
                                <td><x-hex.tag :unit="$sale->costCenter->name" /></td>
                                <td class="num amt"><x-hex.money :amount="$sale->total_invoiced" :decimals="2" /></td>
                                <td class="num amt rec"><x-hex.money :amount="$sale->total_received" :decimals="2" /></td>
                                <td class="num amt pay"><x-hex.money :amount="$sale->balance" :decimals="2" /></td>
                                <td class="desc-cell">{{ $sale->notes }}</td>
                                @can('update', $sale)
                                    <td class="num"><div class="row-actions">
                                        <button type="button" wire:click="openEditForm({{ $sale->id }})" class="tbl-icon-btn"><x-hex.icon name="edit" /></button>
                                        <button type="button" wire:click="delete({{ $sale->id }})" wire:confirm="Delete?" class="tbl-icon-btn danger"><x-hex.icon name="trash" /></button>
                                    </div></td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 pb-4">{{ $sales->links() }}</div>
            @else
                <x-hex.empty-state title="No sales" description="Nothing recorded for this selection." />
            @endif
        </div>
    </div>

    @if ($showForm)
        <div class="modal-overlay show"><div class="modal">
            <div class="modal-head"><h3>{{ $editingId ? 'Edit Sale' : 'Add Sale' }}</h3><button type="button" wire:click="closeForm" class="tbl-icon-btn"><x-hex.icon name="x" /></button></div>
            <form wire:submit="save" class="modal-body"><div class="form-grid">
                <label><span>Unit</span><select wire:model="formCostCenterId">@foreach($visibleUnits as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></label>
                <label><span>Customer</span><input type="text" wire:model="formCustomer"></label>
                <label><span>Invoiced</span><input type="number" step="0.01" wire:model="formInvoiced"></label>
                <label><span>Received</span><input type="number" step="0.01" wire:model="formReceived"></label>
                <label class="span-2"><span>Notes</span><input type="text" wire:model="formNotes"></label>
            </div><div class="modal-foot"><button type="button" wire:click="closeForm" class="hex-btn">Cancel</button><button type="submit" class="hex-btn hex-btn-primary">Save</button></div></form>
        </div></div>
    @endif
</div>
