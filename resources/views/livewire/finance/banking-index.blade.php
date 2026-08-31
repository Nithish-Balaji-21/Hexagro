<div>
    <x-hex.page-head title="Banking" :subtitle="$position ? 'As of '.$position->snapshot->as_of_date->format('d M Y') : 'No snapshot recorded'">
        @can('create', App\Models\BankingSnapshot::class)
            <x-slot:actions>
                <button type="button" wire:click="openCreateForm" class="hex-btn hex-btn-primary">
                    <x-hex.icon name="plus" /> New
                </button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>

    @if ($position)
        @php $s = $position->snapshot; $ccPct = $s->cc_limit > 0 ? round(((float)$s->cc_utilised / (float)$s->cc_limit) * 100) : 0; @endphp
        <div class="bank-grid">
            <div class="bank-card">
                <h4>Union Bank — Current Account</h4>
                <div class="bank-line"><span>Current balance</span><b><x-hex.money :amount="$s->current_balance" /></b></div>
            </div>
            <div class="bank-card">
                <h4>Union Bank — Cash Credit (CC)</h4>
                <div class="bank-line"><span>CC limit</span><b><x-hex.money :amount="$s->cc_limit" /></b></div>
                <div class="bank-line"><span>CC utilised</span><b><x-hex.money :amount="$s->cc_utilised" /></b></div>
                <div class="progress-bar"><div class="fill" style="width: {{ $ccPct }}%"></div></div>
                <div class="progress-label"><span>{{ $ccPct }}% utilised</span><span><x-hex.money :amount="$position->ccAvailable" /> available</span></div>
            </div>
            <div class="bank-card">
                <h4>Union Bank — Term Loan</h4>
                <div class="bank-line"><span>TL limit</span><b><x-hex.money :amount="$s->tl_limit" /></b></div>
                <div class="bank-line"><span>Outstanding</span><b><x-hex.money :amount="$s->term_loan" /></b></div>
            </div>
        </div>
    @else
        <x-hex.empty-state title="No banking snapshot" description="An admin can record the first snapshot." />
    @endif

    <div class="card mt-6">
        <div class="card-head">
            <h3>Banking Snapshot History</h3>
        </div>
        <div class="table-scroll">
            @if ($snapshots->count())
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>As of Date</th>
                            <th class="num">Current Balance</th>
                            <th class="num">CC Utilised</th>
                            <th class="num">CC Limit</th>
                            <th class="num">Term Loan Outstanding</th>
                            <th class="num">TL Limit</th>
                            <th>Created By</th>
                            @can('create', App\Models\BankingSnapshot::class)
                                <th class="num">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshots as $snap)
                            <tr wire:key="snap-{{ $snap->id }}">
                                <td class="mono font-semibold">{{ $snap->as_of_date->format('d M Y') }}</td>
                                <td class="num amt"><x-hex.money :amount="$snap->current_balance" /></td>
                                <td class="num amt"><x-hex.money :amount="$snap->cc_utilised" /></td>
                                <td class="num amt"><x-hex.money :amount="$snap->cc_limit" /></td>
                                <td class="num amt"><x-hex.money :amount="$snap->term_loan" /></td>
                                <td class="num amt"><x-hex.money :amount="$snap->tl_limit" /></td>
                                <td>{{ $snap->createdBy->name ?? '—' }}</td>
                                @can('create', App\Models\BankingSnapshot::class)
                                    <td class="num">
                                        <div class="row-actions">
                                            <button type="button" wire:click="openEditForm({{ $snap->id }})" class="tbl-icon-btn" title="Edit">
                                                <x-hex.icon name="edit" />
                                            </button>
                                            <button type="button" wire:click="delete({{ $snap->id }})" wire:confirm="Delete this banking snapshot?" class="tbl-icon-btn danger" title="Delete">
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
                <x-hex.empty-state title="No banking history" description="No snapshot logs recorded yet." />
            @endif
        </div>
        @if ($snapshots->count())
            <div class="px-4 pb-4 mt-4">{{ $snapshots->links() }}</div>
        @endif
    </div>



    @if ($showForm)
        <div class="modal-overlay show">
            <div class="modal wide">
                <div class="modal-head">
                    <div>
                        <h3>Banking Snapshot</h3>
                        <p style="margin:2px 0 0; color:var(--text-3); font-size:12px;">Update bank account balances and credit limits as per bank statement</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="tbl-icon-btn" title="Close"><x-hex.icon name="x" /></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="background: var(--surface-2);">
                        <div class="banking-as-of-card mb-4">
                            <label class="banking-field">
                                <span class="as-of-label"><x-hex.icon name="calendar" /> As of date</span>
                                <input type="date" wire:model="formAsOf" class="banking-input date-input">
                                @error('formAsOf') <span class="field-error">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="banking-form-grid">
                            <!-- Card 1: Bank — Current Account -->
                            <div class="banking-account-card">
                                <div class="account-card-head">
                                    <div class="ac-icon current-icon"><x-hex.icon name="bank" /></div>
                                    <div class="ac-title">
                                        <h4>Union Bank — Current Account</h4>
                                        <span>Operating account balance</span>
                                    </div>
                                </div>
                                <div class="account-card-body">
                                    <label class="banking-field">
                                        <span>Current balance</span>
                                        <div class="banking-input-group">
                                            <span class="curr-prefix">₹</span>
                                            <input type="number" step="0.01" wire:model="formCurrent" placeholder="0.00" class="banking-input">
                                        </div>
                                        @error('formCurrent') <span class="field-error">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                            </div>

                            <!-- Card 2: Bank — Cash Credit -->
                            <div class="banking-account-card">
                                <div class="account-card-head">
                                    <div class="ac-icon cc-icon"><x-hex.icon name="bank" /></div>
                                    <div class="ac-title">
                                        <h4>Union Bank — Cash Credit (CC)</h4>
                                        <span>Sanctioned credit limit &amp; utilised</span>
                                    </div>
                                </div>
                                <div class="account-card-body dual-fields">
                                    <label class="banking-field">
                                        <span>CC limit</span>
                                        <div class="banking-input-group">
                                            <span class="curr-prefix">₹</span>
                                            <input type="number" step="0.01" wire:model="formCcLimit" placeholder="0.00" class="banking-input">
                                        </div>
                                        @error('formCcLimit') <span class="field-error">{{ $message }}</span> @enderror
                                    </label>
                                    <label class="banking-field">
                                        <span>CC utilised</span>
                                        <div class="banking-input-group">
                                            <span class="curr-prefix">₹</span>
                                            <input type="number" step="0.01" wire:model="formCcUtilised" placeholder="0.00" class="banking-input">
                                        </div>
                                        @error('formCcUtilised') <span class="field-error">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                            </div>

                            <!-- Card 3: Bank — Term Loan -->
                            <div class="banking-account-card">
                                <div class="account-card-head">
                                    <div class="ac-icon tl-icon"><x-hex.icon name="bank" /></div>
                                    <div class="ac-title">
                                        <h4>Union Bank — Term Loan</h4>
                                        <span>Sanctioned limit &amp; outstanding</span>
                                    </div>
                                </div>
                                <div class="account-card-body dual-fields">
                                    <label class="banking-field">
                                        <span>TL limit</span>
                                        <div class="banking-input-group">
                                            <span class="curr-prefix">₹</span>
                                            <input type="number" step="0.01" wire:model="formTlLimit" placeholder="0.00" class="banking-input">
                                        </div>
                                        @error('formTlLimit') <span class="field-error">{{ $message }}</span> @enderror
                                    </label>
                                    <label class="banking-field">
                                        <span>Term loan outstanding</span>
                                        <div class="banking-input-group">
                                            <span class="curr-prefix">₹</span>
                                            <input type="number" step="0.01" wire:model="formTermLoan" placeholder="0.00" class="banking-input">
                                        </div>
                                        @error('formTermLoan') <span class="field-error">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button type="button" wire:click="closeForm" class="hex-btn">Cancel</button>
                        <button type="submit" class="hex-btn hex-btn-primary"><x-hex.icon name="check" /> Save Banking Snapshot</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
