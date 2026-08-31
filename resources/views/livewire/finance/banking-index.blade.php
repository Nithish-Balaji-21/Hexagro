<div>
    <x-hex.page-head title="Banking" :subtitle="$position ? 'As of '.$position->snapshot->as_of_date->format('d M Y') : 'No snapshot recorded'">
        @can('create', App\Models\BankingSnapshot::class)
            <x-slot:actions>
                <button type="button" wire:click="openEditForm" class="hex-btn hex-btn-primary"><x-hex.icon name="edit" /> {{ $position ? 'Update Snapshot' : 'Add Snapshot' }}</button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>

    <x-hex.explain-card title="Accounts (3)">
        The business runs on three Union Bank accounts — Current, Cash Credit and Term Loan — plus Alam Cocos, computed from Payable to Alam transactions in the <a href="{{ route('ledger-book') }}" wire:navigate style="color: inherit; font-weight: 600;">Ledger Book</a>.
    </x-hex.explain-card>

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
            <div class="bank-card">
                <h4>Alam Cocos (non-bank) <span style="font-weight:400;color:var(--text-3);">· computed from transactions</span></h4>
                <div class="bank-line"><span>Alam funds utilised</span><b><x-hex.money :amount="$position->alamUtilised" /></b></div>
                <div class="bank-line"><span>Payable to Alam</span><b class="pay"><x-hex.money :amount="$position->alamPayable" /></b></div>
            </div>
        </div>
    @else
        <x-hex.empty-state title="No banking snapshot" description="An admin can record the first snapshot." />
    @endif

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
