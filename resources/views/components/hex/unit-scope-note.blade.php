@props([
    'allSelected' => true,
    'label' => '',
    'action' => 'see another unit',
])

@if (false)
    <div {{ $attributes->merge(['class' => 'unit-locked-note']) }}>
        <x-hex.icon name="grid" class="w-[13px] h-[13px]" />
        Showing <b class="text-[var(--text)]">{{ $label }}</b> only — switch units from the top bar to {{ $action }}.
    </div>
@endif
