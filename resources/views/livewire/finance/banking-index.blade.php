<div>
    <x-hex.page-head title="Banking" :subtitle="$position ? 'As of '.$position->snapshot->as_of_date->format('d M Y') : 'No snapshot recorded'">
        @can('create', App\Models\BankingSnapshot::class)
            <x-slot:actions>
                <button type="button" wire:click="openEditForm" class="hex-btn hex-btn-primary"><x-hex.icon name="edit" /> {{ $position ? 'Update Snapshot' : 'Add Snapshot' }}</button>
            </x-slot:actions>
        @endcan
    </x-hex.page-head>

    <x-hex.explain-card title="Accounts (3)">
        The business runs on three Union Bank accounts — Current, Cash Credit and Term Loan — plus Alam Cocos tracked separately.
    </x-hex.explain-card>

    @if ($position)
        @php $s = $position->snapshot; $ccPct = $s->cc_limit > 0 ? round(((float)$s->cc_utilised / (float)$s->cc_limit) * 100) : 0; @endphp
        <div class="bank-grid">
            <div class="bank-card">
                <h4>Union Bank — Current Account</h4>
                <div class="bank-line"><span>Current balance</span><b><x-hex.money :amount="$s->current_balance" :decimals="2" /></b></div>
            </div>
            <div class="bank-card">
                <h4>Union Bank — Cash Credit (CC)</h4>
                <div class="bank-line"><span>CC limit</span><b><x-hex.money :amount="$s->cc_limit" :decimals="2" /></b></div>
                <div class="bank-line"><span>CC utilised</span><b><x-hex.money :amount="$s->cc_utilised" :decimals="2" /></b></div>
                <div class="progress-bar"><div class="fill" style="width: {{ $ccPct }}%"></div></div>
                <div class="progress-label"><span>{{ $ccPct }}% utilised</span><span><x-hex.money :amount="$position->ccAvailable" :decimals="2" /> available</span></div>
            </div>
            <div class="bank-card">
                <h4>Union Bank — Term Loan</h4>
                <div class="bank-line"><span>Outstanding</span><b><x-hex.money :amount="$s->term_loan" :decimals="2" /></b></div>
            </div>
            <div class="bank-card">
                <h4>Alam Cocos (non-bank)</h4>
                <div class="bank-line"><span>Alam funds utilised</span><b><x-hex.money :amount="$s->alam_utilised" :decimals="2" /></b></div>
                <div class="bank-line"><span>Payable to Alam</span><b class="pay"><x-hex.money :amount="$position->alamPayable" :decimals="2" /></b></div>
            </div>
        </div>
    @else
        <x-hex.empty-state title="No banking snapshot" description="An admin can record the first snapshot." />
    @endif

    @if ($showForm)
        <div class="modal-overlay show"><div class="modal">
            <div class="modal-head"><h3>Banking Snapshot</h3><button type="button" wire:click="closeForm" class="tbl-icon-btn"><x-hex.icon name="x" /></button></div>
            <form wire:submit="save" class="modal-body"><div class="form-grid">
                <label><span>As of date</span><input type="date" wire:model="formAsOf"></label>
                <label><span>Current balance</span><input type="number" step="0.01" wire:model="formCurrent"></label>
                <label><span>CC limit</span><input type="number" step="0.01" wire:model="formCcLimit"></label>
                <label><span>CC utilised</span><input type="number" step="0.01" wire:model="formCcUtilised"></label>
                <label><span>Term loan</span><input type="number" step="0.01" wire:model="formTermLoan"></label>
                <label><span>Alam utilised</span><input type="number" step="0.01" wire:model="formAlamUtilised"></label>
            </div><div class="modal-foot"><button type="button" wire:click="closeForm" class="hex-btn">Cancel</button><button type="submit" class="hex-btn hex-btn-primary">Save</button></div></form>
        </div></div>
    @endif
</div>
