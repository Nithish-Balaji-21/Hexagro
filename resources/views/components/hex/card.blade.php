@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'hex-card card']) }}>
    @if ($title)
        <div class="card-head">
            <h3>{{ $title }}</h3>
        </div>
    @endif

    <div class="card-pad">
        {{ $slot }}
    </div>
</div>
