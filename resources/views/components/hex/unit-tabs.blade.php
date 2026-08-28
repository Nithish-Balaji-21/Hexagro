@props([
    'units',
    'active' => '',
])

@if ($units->count() > 1)
    <div {{ $attributes->merge(['class' => 'tabs']) }}>
        <button
            type="button"
            wire:click="$set('unitTab', '')"
            class="tab-btn {{ $active === '' ? 'active' : '' }}"
        >
            All Units
        </button>
        @foreach ($units as $unit)
            <button
                type="button"
                wire:click="$set('unitTab', '{{ $unit->id }}')"
                class="tab-btn {{ (string) $active === (string) $unit->id ? 'active' : '' }}"
            >
                {{ $unit->name }}
            </button>
        @endforeach
    </div>
@endif
