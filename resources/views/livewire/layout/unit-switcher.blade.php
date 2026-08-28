@if ($locked)
    <div class="unit-switch locked" title="You have access to {{ $visibleUnits->first()?->name }} only">
        <span class="tag {{ $visibleUnits->first()?->name === 'Fibre Unit' ? 'tag-fibre' : ($visibleUnits->first()?->name === 'Chips Unit' ? 'tag-chips' : 'tag-washing') }}">
            {{ $visibleUnits->first()?->name }}
        </span>
    </div>
@else
    <div class="unit-switch multi" title="Select one or more units">
        <button
            type="button"
            wire:click="selectAll"
            class="unit-pill {{ $this->isAllSelected() ? 'active' : '' }}"
        >
            <x-hex.icon name="grid" />
            <span>All Units</span>
        </button>

        @foreach ($visibleUnits as $unit)
            @php
                $selected = in_array($unit->id, $selectedIds, true);
                $dotClass = match ($unit->name) {
                    'Fibre Unit' => 'unit-fibre',
                    'Chips Unit' => 'unit-chips',
                    'Washing Unit' => 'unit-washing',
                    default => '',
                };
            @endphp
            <button
                type="button"
                wire:click="toggleUnit({{ $unit->id }})"
                class="unit-pill {{ $selected ? 'active' : '' }}"
                aria-pressed="{{ $selected ? 'true' : 'false' }}"
            >
                <span class="pill-check {{ $selected ? 'checked' : '' }}">
                    @if ($selected)
                        <x-hex.icon name="check" class="w-[9px] h-[9px]" />
                    @endif
                </span>
                <span class="dot {{ $dotClass }}"></span>
                <span>{{ $unit->name }}</span>
            </button>
        @endforeach
    </div>
@endif
