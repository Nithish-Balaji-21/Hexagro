<div>
    <x-hex.page-head
        title="Import Data"
        subtitle="Upload history and revert previous import runs"
    >
        <x-slot:actions>
            <a href="{{ route($kind === 'credit' ? 'credit' : ($kind === 'transfers' ? 'transfers' : 'debit')) }}" wire:navigate class="hex-btn">
                <x-hex.icon name="chev-left" />
                Back to {{ $kind === 'credit' ? 'Credit' : ($kind === 'transfers' ? 'Transfers' : 'Debit') }}
            </a>
        </x-slot:actions>
    </x-hex.page-head>

    <div class="card">
        <div class="card-head">
            <h3>Import Run History</h3>
        </div>
        <div class="table-scroll">
            @if ($runs->count())
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Import ID</th>
                            <th>Date / Time</th>
                            <th>Kind</th>
                            <th>Filename</th>
                            <th class="num">Rows Imported</th>
                            <th>User</th>
                            <th class="num">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runs as $run)
                            <tr wire:key="run-{{ $run->id }}">
                                <td class="mono font-semibold">#{{ $run->id }}</td>
                                <td class="mono">{{ $run->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <span class="tag {{ $run->kind === 'debit' ? 'tag-chips' : ($run->kind === 'credit' ? 'tag-fibre' : 'tag-washing') }}">
                                        {{ ucfirst($run->kind) }}
                                    </span>
                                </td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $run->filename }}</td>
                                <td class="num">
                                    <button
                                        type="button"
                                        wire:click="showRunDetails({{ $run->id }})"
                                        class="link-btn mono num"
                                        title="View imported rows"
                                    >
                                        {{ $run->row_count }}
                                    </button>
                                </td>
                                <td>{{ $run->user->name ?? '—' }}</td>
                                <td class="num">
                                    <button
                                        type="button"
                                        wire:click="revertRun({{ $run->id }})"
                                        wire:confirm="Revert import of {{ $run->filename }} ({{ $run->row_count }} rows)? This will delete all imported transactions and cannot be undone."
                                        class="tbl-icon-btn danger"
                                        title="Revert import run"
                                    >
                                        <x-hex.icon name="revert" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-hex.empty-state title="No previous imports" description="Import run history will appear here once you upload data." />
            @endif
        </div>
        @if ($runs->count())
            <div class="px-4 pb-4 mt-4">{{ $runs->links() }}</div>
        @endif
    </div>

    @if ($showDetail && $selectedRun)
        <div class="modal-overlay show" wire:click="closeDetail">
            <div class="modal wide" wire:click.stop>
                <div class="modal-head">
                    <h3>Import #{{ $selectedRun->id }} — {{ $selectedRun->filename }}</h3>
                    <button type="button" wire:click="closeDetail" class="tbl-icon-btn" title="Close">
                        <x-hex.icon name="x" />
                    </button>
                </div>
                <div class="modal-body">
                    <p class="hint mb-4">
                        {{ ucfirst($selectedRun->kind) }} · {{ $selectedRun->created_at->format('d M Y H:i') }} · {{ $selectedRun->row_count }} row(s)
                    </p>

                    @if ($selectedRun->detailRows()->isNotEmpty())
                        <div class="table-scroll">
                            @switch($selectedRun->kind)
                                @case('debit')
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Cost Center</th>
                                                <th>Type</th>
                                                <th>Account</th>
                                                <th>Paid Through</th>
                                                <th>Description</th>
                                                <th class="num">Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($selectedRun->detailRows() as $transaction)
                                                <tr wire:key="detail-debit-{{ $transaction->id }}">
                                                    <td class="mono">{{ $transaction->txn_date->format('d M Y') }}</td>
                                                    <td><x-hex.tag :unit="$transaction->costCenter->name" /></td>
                                                    <td>{{ $transaction->category === App\Enums\DebitCategory::Expense ? 'Expense' : 'Raw Materials' }}</td>
                                                    <td>{{ $transaction->account }}</td>
                                                    <td>{{ $transaction->paidThrough->name }}</td>
                                                    <td class="desc-cell" title="{{ $transaction->description }}">{{ $transaction->description }}</td>
                                                    <td class="num amt"><x-hex.money :amount="$transaction->amount" /></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @break

                                @case('credit')
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Cost Center</th>
                                                <th>Type</th>
                                                <th>Received To</th>
                                                <th>Description</th>
                                                <th class="num">Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($selectedRun->detailRows() as $transaction)
                                                <tr wire:key="detail-credit-{{ $transaction->id }}">
                                                    <td class="mono">{{ $transaction->txn_date->format('d M Y') }}</td>
                                                    <td><x-hex.tag :unit="$transaction->costCenter->name" /></td>
                                                    <td>{{ str($transaction->credit_type->name)->headline() }}</td>
                                                    <td>{{ $transaction->receivedTo->name }}</td>
                                                    <td class="desc-cell" title="{{ $transaction->description }}">{{ $transaction->description }}</td>
                                                    <td class="num amt rec">+<x-hex.money :amount="$transaction->amount" /></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @break

                                @case('transfers')
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Cost Center</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Note</th>
                                                <th class="num">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($selectedRun->detailRows() as $transfer)
                                                <tr wire:key="detail-transfer-{{ $transfer->id }}">
                                                    <td class="mono">{{ $transfer->txn_date->format('d M Y') }}</td>
                                                    <td><x-hex.tag :unit="$transfer->costCenter->name" /></td>
                                                    <td>{{ $transfer->fromEntity->short_name }}</td>
                                                    <td>{{ $transfer->toEntity->short_name }}</td>
                                                    <td class="desc-cell">{{ $transfer->note }}</td>
                                                    <td class="num amt"><x-hex.money :amount="$transfer->amount" /></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @break
                            @endswitch
                        </div>
                    @else
                        <x-hex.empty-state title="No imported rows" description="No transaction records are linked to this import run." />
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
