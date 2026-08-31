<div class="unit-switch {{ $locked ? 'locked' : 'multi' }}" title="{{ $locked ? 'You have access to ' . $visibleUnits->first()?->name . ' only' : 'Select a unit or view all' }}">
    @if ($locked)
        <span class="tag {{ $visibleUnits->first()?->name === 'Fibre Unit' ? 'tag-fibre' : ($visibleUnits->first()?->name === 'Chips Unit' ? 'tag-chips' : 'tag-washing') }}">
            {{ $visibleUnits->first()?->name }}
        </span>
    @else
        <button
            type="button"
            wire:click="selectAll"
            class="unit-pill {{ $allSelected ? 'active' : '' }}"
            aria-pressed="{{ $allSelected ? 'true' : 'false' }}"
        >
            <x-hex.icon name="grid" />
            <span>All Units</span>
        </button>

        @foreach ($visibleUnits as $unit)
            @php
                $selected = in_array($unit->name, $selectedUnits, true) || in_array($unit->id, $selectedIds, true);
                $dotClass = match ($unit->name) {
                    'Fibre Unit' => 'unit-fibre',
                    'Chips Unit' => 'unit-chips',
                    'Washing Unit' => 'unit-washing',
                    default => '',
                };
            @endphp
            <button
                type="button"
                wire:key="unit-pill-{{ $unit->id }}"
                wire:click="toggleUnit('{{ $unit->name }}')"
                class="unit-pill {{ $selected ? 'active' : '' }}"
                aria-pressed="{{ $selected ? 'true' : 'false' }}"
            >
                <span class="pill-check {{ $selected ? 'checked' : '' }}">
                    @if ($selected)
                        <x-hex.icon name="check" class="w-[9px] h-[9px]" />
                    @endif
                </span>
                <span>{{ $unit->name }}</span>
            </button>
        @endforeach
    @endif
</div>
