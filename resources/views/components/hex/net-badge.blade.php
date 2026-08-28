@props(['amount'])

@php
    $value = (float) $amount;
    $tolerance = (float) config('hexagro.settlement_balanced_tolerance', 1.0);
@endphp

@if (abs($value) < $tolerance)
    <span {{ $attributes->merge(['class' => 'net-badge balanced']) }}>
        <x-hex.icon name="check" /> Balanced
    </span>
@elseif ($value > 0)
    <span {{ $attributes->merge(['class' => 'net-badge receive']) }}>
        <x-hex.icon name="up" /> To Receive · <x-hex.money :amount="$value" :decimals="2" />
    </span>
@else
    <span {{ $attributes->merge(['class' => 'net-badge pay']) }}>
        <x-hex.icon name="down" /> To Pay · <x-hex.money :amount="abs($value)" :decimals="2" />
    </span>
@endif
