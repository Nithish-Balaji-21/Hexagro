@props([
    'preset' => 'ytd',
    'from' => '',
    'to' => '',
])

@php
    $range = \App\Support\DateRange::fromState($preset, $from ?: null, $to ?: null);
@endphp

<div {{ $attributes->merge(['class' => 'range-picker']) }}>
    <div class="range-pills">
        @foreach (['ytd' => 'YTD', '7d' => '7D', '1m' => '1M', 'custom' => 'Custom'] as $key => $label)
            <button
                type="button"
                wire:click="setRangePreset('{{ $key }}')"
                class="range-pill {{ $preset === $key ? 'active' : '' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($preset === 'custom')
        <span class="range-custom">
            <input type="date" wire:model.live="rangeFrom">
            <span>to</span>
            <input type="date" wire:model.live="rangeTo">
        </span>
    @else
        <span class="range-custom">{{ $range->label() }}</span>
    @endif
</div>
