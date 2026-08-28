@props([
    'amount',
    'decimals' => 0,
    'class' => '',
    'prefix' => '',
])

<span {{ $attributes->merge(['class' => 'font-mono '.$class]) }}>
    {{ $prefix }}{{ \App\Support\Inr::format($amount, $decimals) }}
</span>
