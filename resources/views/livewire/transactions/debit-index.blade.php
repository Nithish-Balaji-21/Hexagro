<div>
    <x-hex.page-head
        title="Debit"
        subtitle="Track business expenses and payments"
    >
        @can('create', App\Models\DebitTransaction::class)
            <x-slot:actions>
                <button type="button" wire:click="$dispatch('open-import', { kind: 'debit' })" class="hex-btn">
                    <x-hex.icon name="upload" />
                    Import
                </button>
                <button type="button" wire:click="openCreateForm" class="hex-btn hex-btn-primary">
                    <x-hex.icon name="plus" />
                    Add Debit
                </button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>

    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" action="see another unit" />

    <x-hex.unit-tabs :units="$scopedUnits" :active="$unitTab" />

    <div class="filter-bar filter-bar-range">
        <span class="fb-label"><x-hex.icon name="calendar" /> Range</span>
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

    <div class="filter-bar">
        <div class="search-box">
            <x-hex.icon name="search" />
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search description or account…">
        </div>
        <select wire:model.live="categoryFilter">
            <option value="both">Expenses + Raw Materials</option>
            <option value="expenses">Expenses only</option>
            <option value="raw">Raw Materials only</option>
        </select>
        <select wire:model.live="paidThroughFilter">
            <option value="">All payees</option>
            @foreach ($entities as $entity)
                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="card">
        <div class="table-scroll">
            @if ($transactions->count())
                <table class="data-table">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('txn_date')" class="sortable">Date</th>
                            <th wire:click="sortBy('cost_center_id')" class="sortable">Cost Center</th>
                            <th>Type</th>
                            <th>Account</th>
                            <th wire:click="sortBy('paid_through_entity_id')" class="sortable">Paid Through</th>
                            <th>Description</th>
                            <th wire:click="sortBy('amount')" class="sortable num">Total Amount</th>
                            @can('create', App\Models\DebitTransaction::class)
                                <th class="num">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr wire:key="debit-{{ $transaction->id }}">
                                <td class="mono">{{ $transaction->txn_date->format('d M Y') }}</td>
                                <td><x-hex.tag :unit="$transaction->costCenter->name" /></td>
                                <td>{{ $transaction->category === App\Enums\DebitCategory::Expense ? 'Expense' : 'Raw Materials' }}</td>
                                <td>{{ $transaction->account }}</td>
                                <td>{{ $transaction->paidThrough->name }}</td>
                                <td class="desc-cell" title="{{ $transaction->description }}">{{ $transaction->description }}</td>
                                <td class="num amt"><x-hex.money :amount="$transaction->amount" :decimals="2" /></td>
                                @can('update', $transaction)
                                    <td class="num">
                                        <div class="row-actions">
                                            <button type="button" wire:click="openEditForm({{ $transaction->id }})" class="tbl-icon-btn">
                                                <x-hex.icon name="edit" />
                                            </button>
                                            <button type="button" wire:click="delete({{ $transaction->id }})" wire:confirm="Delete this debit transaction?" class="tbl-icon-btn danger">
                                                <x-hex.icon name="trash" />
                                            </button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-hex.empty-state
                    title="No transactions found"
                    description="Try adjusting your filters, or add a debit record."
                />
            @endif
        </div>

        @if ($transactions->count())
            <div class="table-foot">
                <span>Total for filtered rows: <b class="font-mono"><x-hex.money :amount="$totalAmount" :decimals="2" /></b></span>
            </div>
            <div class="px-4 pb-4">{{ $transactions->links() }}</div>
        @endif
    </div>

    @if ($showForm)
        <div class="modal-overlay show">
            <div class="modal">
                <div class="modal-head">
                    <h3>{{ $editingId ? 'Edit Debit' : 'Add Debit' }}</h3>
                    <button type="button" wire:click="closeForm" class="tbl-icon-btn"><x-hex.icon name="x" /></button>
                </div>
                <form wire:submit="save" class="modal-body">
                    <div class="form-grid">
                        <label>
                            <span>Date</span>
                            <input type="date" wire:model="formDate">
                            @error('formDate') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label>
                            <span>Cost Center</span>
                            <select wire:model="formCostCenterId">
                                @foreach ($scopedUnits as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Category</span>
                            <select wire:model="formCategory">
                                <option value="EXPENSE">Expense</option>
                                <option value="RAW_MATERIALS">Raw Materials</option>
                            </select>
                        </label>
                        <label>
                            <span>Account</span>
                            <input type="text" wire:model="formAccount">
                            @error('formAccount') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label>
                            <span>Paid Through</span>
                            <select wire:model="formPaidThroughId">
                                @foreach ($entities as $entity)
                                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Amount</span>
                            <input type="number" step="0.01" min="0.01" wire:model="formAmount">
                            @error('formAmount') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="span-2">
                            <span>Description</span>
                            <input type="text" wire:model="formDescription">
                        </label>
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
