@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'page-head']) }}>
    <div>
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <div class="sub">{{ $subtitle }}</div>
        @endif
    </div>

    @if (isset($actions))
        <div class="page-actions">
            {{ $actions }}
        </div>
    @endif
</div>
