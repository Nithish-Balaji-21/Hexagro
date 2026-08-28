@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <rect x="3" y="3" width="18" height="18" rx="3" />
        <path d="M8 12h8M12 8v8" />
    </svg>
    <div class="empty-state-title">{{ $title }}</div>
    @if ($description)
        <div class="empty-state-desc">{{ $description }}</div>
    @endif
    @if (isset($action))
        <div class="empty-state-action">{{ $action }}</div>
    @endif
</div>
