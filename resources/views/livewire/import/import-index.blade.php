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
                                <td class="mono num">{{ $run->row_count }}</td>
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
</div>
