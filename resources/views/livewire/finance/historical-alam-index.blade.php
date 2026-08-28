<div>
    <x-hex.page-head title="Historical Alam Expenses" subtitle="Fibre Unit expenses funded by Alam before settlement began">
        @can('create', App\Models\HistoricalAlamExpense::class)
            <x-slot:actions>
                <button type="button" wire:click="openCreateForm" class="hex-btn hex-btn-primary"><x-hex.icon name="plus" /> Add Entry</button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>

    <x-hex.explain-card title="How this feeds into Settlement">
        {{ number_format((float) $sharePct * 100, 2) }}% of this historical spend is folded into Alam Share on the Fibre Unit settlement.
    </x-hex.explain-card>

    <div class="kpi-grid mb-5" style="grid-template-columns: repeat(2, 1fr);">
        <x-hex.kpi-card label="Total Historical Alam Expenses" :value="\App\Support\Inr::format($total, 2)" />
        <x-hex.kpi-card label="Alam Contribution Share" :value="\App\Support\Inr::format($shareAmount, 2)" />
    </div>

    <div class="filter-bar">
        <div class="search-box"><x-hex.icon name="search" /><input type="text" wire:model.live.debounce.300ms="search" placeholder="Search description or account…"></div>
    </div>

    <div class="card">
        <div class="table-scroll">
            @if ($entries->count())
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Account</th><th>Description</th><th class="num">Amount</th>@can('create', App\Models\HistoricalAlamExpense::class)<th class="num">Actions</th>@endcan</tr></thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr wire:key="ha-{{ $entry->id }}">
                                <td class="mono">{{ $entry->txn_date->format('d M Y') }}</td>
                                <td>{{ $entry->account }}</td>
                                <td class="desc-cell">{{ $entry->description }}</td>
                                <td class="num amt"><x-hex.money :amount="$entry->amount" :decimals="2" /></td>
                                @can('update', $entry)
                                    <td class="num"><div class="row-actions">
                                        <button type="button" wire:click="openEditForm({{ $entry->id }})" class="tbl-icon-btn"><x-hex.icon name="edit" /></button>
                                        <button type="button" wire:click="delete({{ $entry->id }})" wire:confirm="Delete?" class="tbl-icon-btn danger"><x-hex.icon name="trash" /></button>
                                    </div></td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 pb-4">{{ $entries->links() }}</div>
            @else
                <x-hex.empty-state title="No entries" description="Try adjusting your search." />
            @endif
        </div>
    </div>

    @if ($showForm)
        <div class="modal-overlay show"><div class="modal">
            <div class="modal-head"><h3>{{ $editingId ? 'Edit Entry' : 'Add Entry' }}</h3><button type="button" wire:click="closeForm" class="tbl-icon-btn"><x-hex.icon name="x" /></button></div>
            <form wire:submit="save" class="modal-body"><div class="form-grid">
                <label><span>Date</span><input type="date" wire:model="formDate"></label>
                <label><span>Account</span><input type="text" wire:model="formAccount"></label>
                <label class="span-2"><span>Description</span><input type="text" wire:model="formDescription"></label>
                <label><span>Amount</span><input type="number" step="0.01" min="0.01" wire:model="formAmount"></label>
            </div><div class="modal-foot"><button type="button" wire:click="closeForm" class="hex-btn">Cancel</button><button type="submit" class="hex-btn hex-btn-primary">Save</button></div></form>
        </div></div>
    @endif
</div>
