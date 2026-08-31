<div>
@if ($show)
    <div class="modal-overlay show">
        <div class="modal wide">
            <div class="modal-head">
                <h3>{{ $this->modalTitle() }} — Excel</h3>
                <div class="modal-head-actions">
                    @if (in_array($kind, ['debit', 'credit', 'transfers', 'outstanding'], true))
                        <a href="{{ route('import.template', $kind) }}" class="tbl-icon-btn" title="Download template" download>
                            <x-hex.icon name="download" />
                        </a>
                    @endif

                    <button type="button" wire:click="close" class="tbl-icon-btn" title="Close"><x-hex.icon name="x" /></button>
                </div>
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
                    <div class="custom-dropzone @if($workbook) has-file @endif">
                        <input type="file" wire:model="workbook" accept=".xlsx,.csv,.txt" id="excel-file-upload" class="hidden-file-input">

                        @if (! $workbook)
                            <div class="dropzone-content">
                                <div class="dropzone-icon-wrapper">
                                    <x-hex.icon name="upload" />
                                </div>
                                <div class="dropzone-text">
                                    <p class="main-label"><b>Drag &amp; drop</b> your Excel export here, or <span class="browse-link">browse</span></p>
                                    <p class="hint-label">{{ $this->modalHint() }}</p>
                                </div>
                                <label for="excel-file-upload" class="hex-btn hex-btn-sm hex-btn-secondary dropzone-btn">
                                    <x-hex.icon name="file" />
                                    Choose File
                                </label>
                            </div>
                        @else
                            <div class="selected-file-card">
                                <div class="file-icon-box">
                                    <x-hex.icon name="file" />
                                </div>
                                <div class="file-info">
                                    <span class="file-name">{{ method_exists($workbook, 'getClientOriginalName') ? $workbook->getClientOriginalName() : 'workbook.xlsx' }}</span>
                                    <span class="file-ready">File attached — ready to preview</span>
                                </div>
                                <label for="excel-file-upload" class="change-file-btn" title="Change file">
                                    Change file
                                </label>
                            </div>
                        @endif
                    </div>
                    @error('workbook') <p class="text-pay text-sm mt-2 text-center">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="workbook,preview" class="hint mt-2 text-center">Processing file…</div>
                @endif

                @if ($step === 2)
                    <div class="import-stats" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="import-stat ok">
                            <b>{{ $this->validCount() }}</b>
                            <span>Valid rows</span>
                        </div>
                        <div class="import-stat err">
                            <b>{{ $this->errorCount() }}</b>
                            <span>Rows with errors</span>
                        </div>
                        <div class="import-stat" style="background:#fffbeb; border:1px solid #fde68a; color:#d97706; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:1.25rem 1rem; border-radius:8px; text-align:center; cursor:help;" title="Transfer fund rows are not allowed in {{ ucfirst($this->kind) }}, so they are skipped. They will be imported via Transfers instead.">
                            <b style="display:block; font-size:24px; font-weight:700; line-height:1; margin-bottom:0.25rem;">{{ $this->skippedCount() }}</b>
                            <span style="font-size:12px; font-weight:500; opacity:0.9;">Skipped rows</span>
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
                                            <tr @if(! $row['valid']) style="background:var(--pay-tint)" @elseif($row['skipped'] ?? false) style="background:#fffdf5" @endif>
                                                <td class="mono">{{ $row['rowNumber'] ?: '—' }}</td>
                                                <td class="mono">{{ $row['date'] ?: '—' }}</td>
                                                <td>{{ $row['costCenter'] ?: '—' }}</td>
                                                <td>{{ $row['detail'] ?: '—' }}</td>
                                                <td class="num amt">{{ $row['amount'] ?: '—' }}</td>
                                                <td>
                                                    @if ($row['skipped'] ?? false)
                                                        <span class="status-badge pending" style="background:#fef3c7;color:#d97706;" title="{{ $row['skipReason'] ?? '' }}">Skipped</span>
                                                    @elseif ($row['valid'])
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

                            @php $sheetSkipped = collect($sheetResult['rows'])->filter(fn($r) => $r['skipped'] ?? false); @endphp
                            @if ($sheetSkipped->isNotEmpty())
                                <div class="info-list mb-4" style="background: #fffbeb; border: 1px solid #fde68a; padding: 0.75rem; border-radius: 6px; font-size: 13px;">
                                    <div style="font-weight: 600; color: #b45309; margin-bottom: 0.5rem; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Information / Skipped Rows</div>
                                    @foreach ($sheetSkipped as $row)
                                        <div class="info-row" style="margin-bottom: 0.25rem;">
                                            <strong style="color: #b45309;">Row {{ $row['rowNumber'] ?: '?' }}:</strong>
                                            <span style="color: #78350f;">{{ $row['skipReason'] ?? '' }}</span>
                                        </div>
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
                    <button
                        type="button"
                        wire:click="confirmImport"
                        wire:loading.attr="disabled"
                        class="hex-btn hex-btn-primary"
                        @disabled($this->validCount() === 0 || $this->errorCount() > 0)
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
