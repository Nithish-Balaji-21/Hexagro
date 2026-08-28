@props([
    'name',
    'class' => '',
])

@php
    $paths = [
        'grid' => 'M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z',
        'down' => 'M12 5v14M5 12l7 7 7-7',
        'up' => 'M12 19V5M5 12l7-7 7 7',
        'scale' => 'M12 3v18M3 12h18',
        'calendar' => 'M4 7h16M7 3v4M17 3v4M5 11h14v10H5V11z',
        'trend' => 'M4 18l5-5 4 4 7-8',
        'bank' => 'M3 10h18M5 10V20M9 10V20M15 10V20M19 10V20M2 20h20',
        'history' => 'M12 8v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'chev-left' => 'M15 18l-6-6 6-6',
        'chev-right' => 'M9 6l6 6-6 6',
        'chev-down' => 'M6 9l6 6 6-6',
        'logout' => 'M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9',
        'check' => 'M5 12l4 4L19 6',
    ];
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    {{ $attributes->merge(['class' => 'hex-icon '.$class]) }}
>
    <path d="{{ $paths[$name] ?? $paths['grid'] }}" />
</svg>
