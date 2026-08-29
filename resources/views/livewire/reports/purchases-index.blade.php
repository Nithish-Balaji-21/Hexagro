<div>
    <x-hex.page-head title="Purchases" subtitle="Raw material vendor ledger — billed, paid and outstanding">
        @can('create', App\Models\Purchase::class)
            <x-slot:actions>
                <button type="button" wire:click="openCreateForm" class="hex-btn hex-btn-primary"><x-hex.icon name="plus" /> Add Purchase</button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>
    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" />

    <div class="filter-bar">
        @if ($scopedUnits->count() > 1)
            <select wire:model.live="unitFilter"><option value="">All units</option>@foreach($scopedUnits as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
        @endif
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search vendor…">
    </div>

    <div class="kpi-grid mb-5" style="grid-template-columns: repeat(3, 1fr);">
        <x-hex.kpi-card label="Total Billed" :value="\App\Support\Inr::format($totalBilled, 2)" />
        <x-hex.kpi-card label="Total Paid" :value="\App\Support\Inr::format($totalPaid, 2)" />
        <x-hex.kpi-card label="Outstanding" :value="\App\Support\Inr::format($totalOutstanding, 2)" />
    </div>

    <div class="card">
        <div class="table-scroll">
            @if ($purchases->count())
                <table class="data-table">
                    <thead><tr><th>Vendor</th><th>Unit</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance</th><th>Notes</th>@can('create', App\Models\Purchase::class)<th class="num">Actions</th>@endcan</tr></thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                            <tr wire:key="pu-{{ $purchase->id }}">
                                <td>{{ $purchase->vendor_name }}</td>
                                <td><x-hex.tag :unit="$purchase->costCenter->name" /></td>
                                <td class="num amt"><x-hex.money :amount="$purchase->total_billed" :decimals="2" /></td>
                                <td class="num amt rec"><x-hex.money :amount="$purchase->total_paid" :decimals="2" /></td>
                                <td class="num amt pay"><x-hex.money :amount="$purchase->balance" :decimals="2" /></td>
                                <td class="desc-cell">{{ $purchase->notes }}</td>
                                @can('update', $purchase)
                                    <td class="num"><div class="row-actions">
                                        <button type="button" wire:click="openEditForm({{ $purchase->id }})" class="tbl-icon-btn"><x-hex.icon name="edit" /></button>
                                        <button type="button" wire:click="delete({{ $purchase->id }})" wire:confirm="Delete?" class="tbl-icon-btn danger"><x-hex.icon name="trash" /></button>
                                    </div></td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 pb-4">{{ $purchases->links() }}</div>
            @else
                <x-hex.empty-state title="No purchases" description="Nothing recorded for this selection." />
            @endif
        </div>
    </div>

    @if ($showForm)
        <div class="modal-overlay show"><div class="modal">
            <div class="modal-head"><h3>{{ $editingId ? 'Edit Purchase' : 'Add Purchase' }}</h3><button type="button" wire:click="closeForm" class="tbl-icon-btn"><x-hex.icon name="x" /></button></div>
            <form wire:submit="save" class="modal-body"><div class="form-grid">
                <label><span>Unit</span><select wire:model="formCostCenterId">@foreach($scopedUnits as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></label>
                <label><span>Vendor</span><input type="text" wire:model="formVendor"></label>
                <label><span>Total Billed</span><input type="number" step="0.01" wire:model="formBilled"></label>
                <label><span>Total Paid</span><input type="number" step="0.01" wire:model="formPaid"></label>
                <label class="span-2"><span>Notes</span><input type="text" wire:model="formNotes"></label>
            </div><div class="modal-foot"><button type="button" wire:click="closeForm" class="hex-btn">Cancel</button><button type="submit" class="hex-btn hex-btn-primary">Save</button></div></form>
        </div></div>
    @endif
</div>
