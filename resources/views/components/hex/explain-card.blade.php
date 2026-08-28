@props(['title'])

<div {{ $attributes->merge(['class' => 'explain-card']) }}>
    <div class="ic"><x-hex.icon name="scale" /></div>
    <div>
        <h4>{{ $title }}</h4>
        <p>{{ $slot }}</p>
    </div>
</div>
