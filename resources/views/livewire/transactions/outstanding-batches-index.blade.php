<div>
    <x-hex.page-head :title="$pageTitle" :subtitle="$batchNote">
        @can('create', App\Models\OutstandingBatch::class)
            @if (! $showBatchForm && $selectedBatchId === null)
                <x-slot:actions>
                    <button type="button" wire:click="openCreateBatch" class="hex-btn hex-btn-primary">
                        <x-hex.icon name="plus" /> New batch
                    </button>
                </x-slot:actions>
            @endif
        @endcan
    </x-hex.page-head>
    <x-hex.unit-scope-note :all-selected="$allSelected" :label="$scopeLabel" />

    @if ($showBatchForm)
        <div class="card card-pad mb-5">
            <div class="outstanding-batch-head">
                <div>
                    <h3 class="text-base font-semibold">{{ $editingBatchId ? 'Edit Batch' : 'New Batch' }}</h3>
                    <p class="hint">Record outstanding payments or receivables by party and unit</p>
                </div>
                <button type="button" wire:click="closeBatchForm" class="tbl-icon-btn" title="Close"><x-hex.icon name="x" /></button>
            </div>

            <form wire:submit="saveBatch">
                <div class="mb-4 flex flex-wrap items-end gap-4 justify-between">
                    <label class="banking-field max-w-[220px]">
                        <span class="as-of-label"><x-hex.icon name="calendar" /> Batch date</span>
                        <input type="date" wire:model="formBatchDate" class="banking-input date-input">
                        @error('formBatchDate') <span class="field-error">{{ $message }}</span> @enderror
                    </label>

                    @if (! $editingBatchId && isset($recentBatches) && count($recentBatches) > 0)
                        <div class="banking-field min-w-[260px]">
                            <span class="as-of-label"><x-hex.icon name="copy" /> Copy lines from batch</span>
                            <select wire:change="loadLinesFromBatch($event.target.value)" class="banking-input cursor-pointer">
                                <option value="0">-- Select batch to copy --</option>
                                @foreach ($recentBatches as $prevBatch)
                                    <option value="{{ $prevBatch->id }}">
                                        {{ \App\Support\Inr::formatDatePicker($prevBatch->batch_date->toDateString()) }} ({{ $prevBatch->lines_count }} items)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="outstanding-table-wrap mt-4">
                    <table class="outstanding-table">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Item / Party</th>
                                <th style="width: 22%;">Cost Center</th>
                                <th class="num" style="width: 20%;">Amount (₹)</th>
                                <th>Notes</th>
                                <th class="num" style="width: 44px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lineRows as $index => $row)
                                <tr wire:key="line-row-{{ $index }}">
                                    <td>
                                        <input type="text" wire:model="lineRows.{{ $index }}.party" placeholder="Party name" class="outstanding-input">
                                        @error('lineRows.'.$index.'.party') <span class="field-error">{{ $message }}</span> @enderror
                                    </td>
                                    <td>
                                        <select wire:model="lineRows.{{ $index }}.cost_center_id" class="outstanding-input">
                                            @foreach ($scopedUnits as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('lineRows.'.$index.'.cost_center_id') <span class="field-error">{{ $message }}</span> @enderror
                                    </td>
                                    <td class="num">
                                        <input type="number" step="0.01" wire:model.live="lineRows.{{ $index }}.amount" placeholder="0.00" class="outstanding-input num-input {{ (float)($row['amount'] ?? 0) < 0 ? 'neg' : '' }}">
                                        @error('lineRows.'.$index.'.amount') <span class="field-error">{{ $message }}</span> @enderror
                                    </td>
                                    <td>
                                        <input type="text" wire:model="lineRows.{{ $index }}.notes" placeholder="Notes (optional)" class="outstanding-input">
                                    </td>
                                    <td class="num">
                                        <button type="button" wire:click="removeLineRow({{ $index }})" class="tbl-icon-btn danger" title="Remove row">
                                            <x-hex.icon name="trash" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><strong>Total outstanding</strong></td>
                                <td class="num amt"><strong><x-hex.money :amount="$lineTotal" /></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="batch-form-actions mt-5">
                    <button type="button" wire:click="addLineRow" class="hex-btn"><x-hex.icon name="plus" /> Add row</button>
                    <div class="batch-form-actions-right">
                        <button type="button" wire:click="closeBatchForm" class="hex-btn">Cancel</button>
                        <button type="submit" class="hex-btn hex-btn-primary"><x-hex.icon name="check" /> Save batch</button>
                    </div>
                </div>
            </form>
        </div>
    @elseif ($batch)
        <div class="mb-4">
            <button type="button" wire:click="backToList" class="hex-btn"><x-hex.icon name="chev-left" /> All batches</button>
        </div>

        <div class="outstanding-detail-card">
            <div class="outstanding-detail-head">
                <div>
                    <h3>{{ $batchHeading }} — as of {{ \App\Support\Inr::formatDatePicker($batch->batch_date->toDateString()) }}</h3>
                    <p class="hint">{{ $batchNote }}</p>
                </div>
                <div class="row-actions">
                    @can('create', App\Models\OutstandingBatch::class)
                        <button type="button" wire:click="copyBatch({{ $batch->id }})" class="hex-btn" title="Copy to new batch"><x-hex.icon name="copy" /> Copy batch</button>
                    @endcan
                    @can('update', $batch)
                        <button type="button" wire:click="openEditBatch({{ $batch->id }})" class="hex-btn"><x-hex.icon name="edit" /> Edit</button>
                        <button type="button" wire:click="deleteBatch({{ $batch->id }})" wire:confirm="Delete this batch?" class="hex-btn danger"><x-hex.icon name="trash" /> Delete</button>
                    @endcan
                </div>
            </div>

            <div class="outstanding-table-wrap">
                <table class="outstanding-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Item / Party</th>
                            <th style="width: 25%;">Cost Center</th>
                            <th class="num" style="width: 20%;">Amount</th>
                            <th style="width: 20%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $detailTotal = '0'; @endphp
                        @foreach ($batch->lines as $line)
                            @php $detailTotal = \App\Support\Money::add($detailTotal, (string) $line->amount); @endphp
                            <tr wire:key="detail-line-{{ $line->id }}">
                                <td>{{ $line->party_name }}</td>
                                <td><x-hex.tag :unit="$line->costCenter->name" /></td>
                                <td class="num amt {{ (float) $line->amount < 0 ? 'rec' : 'pay' }}">
                                    <x-hex.money :amount="$line->amount" />
                                </td>
                                <td class="desc-cell">{{ $line->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2"><strong>Total outstanding</strong></td>
                            <td class="num amt"><strong><x-hex.money :amount="$detailTotal" /></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-head"><h3>Batches</h3></div>
            <div class="table-scroll">
                @if ($batches && $batches->count())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Batch date</th>
                                <th class="num">Lines</th>
                                <th class="num">Total outstanding</th>
                                <th>Created by</th>
                                <th class="num">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($batches as $item)
                                <tr wire:key="batch-{{ $item->id }}">
                                    <td class="mono font-semibold">{{ \App\Support\Inr::formatDatePicker($item->batch_date->toDateString()) }}</td>
                                    <td class="num">{{ $item->lines_count }}</td>
                                    <td class="num amt pay"><x-hex.money :amount="$item->scoped_total ?? 0" /></td>
                                    <td>{{ $item->createdBy->name ?? '—' }}</td>
                                    <td class="num">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" wire:click="viewBatch({{ $item->id }})" class="hex-btn hex-btn-sm">View</button>
                                            @can('create', App\Models\OutstandingBatch::class)
                                                <button type="button" wire:click="copyBatch({{ $item->id }})" class="hex-btn hex-btn-sm" title="Copy to new batch">
                                                    <x-hex.icon name="copy" /> Copy
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-4 pb-4">{{ $batches->links() }}</div>
                @else
                    <x-hex.empty-state title="No batches" description="Create a batch to record outstanding balances." />
                @endif
            </div>
        </div>
    @endif
</div>
