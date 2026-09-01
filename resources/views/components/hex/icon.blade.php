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
        'plus' => 'M12 5v14M5 12h14',
        'edit' => 'M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z',
        'trash' => 'M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6',
        'search' => 'M11 19a8 8 0 100-16 8 8 0 000 16zM21 21l-4.3-4.3',
        'layers' => 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
        'clock' => 'M12 8v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'x' => 'M18 6L6 18M6 6l12 12',
        'upload' => 'M12 16V4M8 8l4-4 4 4M4 20h16',
        'download' => 'M12 4v12M8 12l4 4 4-4M4 20h16',
        'file' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zM14 2v6h6',
        'revert' => 'M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8M3 3v5h5',
        'transfer' => 'm16 3 4 4-4 4M20 7H4M8 21l-4-4 4-4M4 17h16',
        'outward' => 'M7 17L17 7M17 7H9M17 7v8',
        'inward' => 'M17 7L7 17M7 17h8M7 17V9',
        'chart' => 'M18 20V10M12 20V4M6 20v-6',
        'copy' => 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z',
        'settlement' => '<path d="m11 17 2 2a1 1 0 1 0 3-3" /><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 6" /><path d="m21 3 1.1 0" /><path d="m4.7 13.9 1.1-1.1" />',
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
    @if (str_starts_with($paths[$name] ?? '', '<'))
        {!! $paths[$name] !!}
    @else
        <path d="{{ $paths[$name] ?? $paths['grid'] }}" />
    @endif
</svg>
