<div>
@if ($show)
    <div class="modal-overlay show">
        <div class="modal wide">
            <div class="modal-head">
                <h3>{{ $this->modalTitle() }} — Excel</h3>
                <button type="button" wire:click="close" class="tbl-icon-btn"><x-hex.icon name="x" /></button>
            </div>

            <div class="modal-body">
                <div class="step-track">
                    <div class="step-dot {{ $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' }}">
                        <span class="n">1</span> Upload
                    </div>
                    <div class="step-line"></div>
                    <div class="step-dot {{ $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' }}">
                        <span class="n">2</span> Preview &amp; validate
                    </div>
                    <div class="step-line"></div>
                    <div class="step-dot {{ $step >= 3 ? 'active' : '' }}">
                        <span class="n">3</span> Confirm
                    </div>
                </div>

                @if ($step === 1)
                    <div class="dropzone">
                        <x-hex.icon name="upload" />
                        <p><b>Drag and drop</b> your Excel export here, or choose a file</p>
                        <input type="file" wire:model="workbook" accept=".xlsx,.csv,.txt" class="mt-3 text-sm">
                        <p class="hint mt-2">Accepts .xlsx or .csv files with Debit, Credit, Transfers, or Outstanding data</p>
                        @error('workbook') <p class="text-pay text-sm mt-2">{{ $message }}</p> @enderror
                        @if ($workbook)
                            <div class="fname mt-3">
                                <x-hex.icon name="file" />
                                {{ method_exists($workbook, 'getClientOriginalName') ? $workbook->getClientOriginalName() : 'workbook.xlsx' }}
                            </div>
                        @endif
                    </div>
                    <div wire:loading wire:target="workbook,preview" class="hint mt-2">Processing file…</div>
                @endif

                @if ($step === 2)
                    <div class="import-stats">
                        <div class="import-stat ok">
                            <b>{{ $this->validCount() }}</b>
                            <span>Valid rows</span>
                        </div>
                        <div class="import-stat err">
                            <b>{{ $this->errorCount() }}</b>
                            <span>Rows with errors</span>
                        </div>
                    </div>

                    @foreach ($this->previewResultsForDisplay() as $sheetResult)
                        <div class="card-head" style="padding:0 0 8px;border:none;">
                            <h3 style="font-size:13px;">{{ $sheetResult['sheet'] }} sheet</h3>
                        </div>
                        @if (count($sheetResult['rows']))
                            <div class="table-scroll import-preview-table mb-4">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Row</th>
                                            <th>Date</th>
                                            <th>Cost Center</th>
                                            <th>{{ $this->detailColumnLabel() }}</th>
                                            <th class="num">Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sheetResult['rows'] as $row)
                                            <tr @if(! $row['valid']) style="background:var(--pay-tint)" @endif>
                                                <td class="mono">{{ $row['rowNumber'] ?: '—' }}</td>
                                                <td class="mono">{{ $row['date'] ?: '—' }}</td>
                                                <td>{{ $row['costCenter'] ?: '—' }}</td>
                                                <td>{{ $row['detail'] ?: '—' }}</td>
                                                <td class="num amt">{{ $row['amount'] ?: '—' }}</td>
                                                <td>
                                                    @if ($row['valid'])
                                                        <span class="status-badge pending" style="background:var(--receive-tint);color:var(--receive);">Valid</span>
                                                    @else
                                                        <span class="status-badge open">Error</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @php $sheetErrors = collect($sheetResult['rows'])->where('valid', false); @endphp
                            @if ($sheetErrors->isNotEmpty())
                                <div class="err-list mb-4">
                                    @foreach ($sheetErrors as $row)
                                        @if ($row['error'])
                                            <div class="err-row">
                                                <b>Row {{ $row['rowNumber'] ?: '?' }}</b>
                                                <span>{{ $row['error'] }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="hint mb-4">No importable rows found in this sheet.</p>
                        @endif
                    @endforeach
                @endif
            </div>

            <div class="modal-foot">
                <button type="button" wire:click="close" class="hex-btn">Cancel</button>

                @if ($step === 1)
                    <button
                        type="button"
                        wire:click="preview"
                        wire:loading.attr="disabled"
                        class="hex-btn hex-btn-primary"
                        @disabled(! $workbook)
                    >
                        Preview rows
                    </button>
                @endif

                @if ($step === 2)
                    <label class="import-skip-label">
                        <input type="checkbox" wire:model="skipErrors">
                        Skip {{ $this->errorCount() }} error row{{ $this->errorCount() === 1 ? '' : 's' }} and import the rest
                    </label>
                    <button
                        type="button"
                        wire:click="confirmImport"
                        wire:loading.attr="disabled"
                        class="hex-btn hex-btn-primary"
                        @disabled($this->validCount() === 0)
                    >
                        <x-hex.icon name="check" />
                        Confirm Import ({{ $this->validCount() }} rows)
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
</div>
