<div>
    <x-hex.page-head
        title="Dashboard"
        subtitle="Business overview — Phase 2 shell"
    />

    <div class="kpi-grid mb-5">
        <x-hex.kpi-card label="Signed in as" :value="auth()->user()->name" />
        <x-hex.kpi-card label="Role" :value="auth()->user()->role->value === 'ADMIN' ? 'Admin' : 'Viewer'" />
        <x-hex.kpi-card label="Unit scope" :value="$scopeLabel" />
        <x-hex.kpi-card label="Selected units" :value="(string) count($selectedUnitNames)" />
    </div>

    <x-hex.card title="Unit scope smoke test">
        <p class="text-[var(--text-2)] text-[13px] mb-4">
            The unit switcher in the top bar filters data across the app. Selected units for this session:
        </p>

        @if (count($selectedUnitNames) > 0)
            <ul class="unit-scope-list">
                @foreach ($selectedUnitNames as $name)
                    <li>
                        <span class="dot {{ $name === 'Fibre Unit' ? 'unit-fibre' : ($name === 'Chips Unit' ? 'unit-chips' : 'unit-washing') }}"></span>
                        {{ $name }}
                    </li>
                @endforeach
            </ul>
        @else
            <x-hex.empty-state
                title="No units in scope"
                description="Your account has no visible cost centers."
            />
        @endif
    </x-hex.card>

    <x-hex.card title="Coming in Phase 3" class="mt-4">
        <x-hex.empty-state
            title="Dashboard KPIs & charts"
            description="Debit, Credit, Settlement and report pages will be built in the next phase."
        />
    </x-hex.card>
</div>
