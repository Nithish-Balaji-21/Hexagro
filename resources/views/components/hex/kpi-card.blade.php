@props([
    'label',
    'value',
    'foot' => null,
])

<div {{ $attributes->merge(['class' => 'kpi-card']) }}>
    <div class="kpi-label">{{ $label }}</div>
    <div class="kpi-value">{{ $value }}</div>
    @if ($foot)
        <div class="kpi-foot">{{ $foot }}</div>
    @endif
</div>
