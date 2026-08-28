@props(['unit'])

@php
    $class = match ($unit) {
        'Fibre Unit' => 'tag-fibre',
        'Chips Unit' => 'tag-chips',
        'Washing Unit' => 'tag-washing',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => 'tag inline-flex items-center gap-1.5']) }}>
    <span class="dot {{ $class }}"></span>
    {{ $unit }}
</span>
