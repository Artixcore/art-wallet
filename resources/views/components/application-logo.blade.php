@props([
    'alt' => null,
])

<img
    src="{{ asset('brand/artwallet-icon.svg') }}"
    alt="{{ $alt ?? config('app.name') }}"
    {{ $attributes->merge(['class' => 'object-contain select-none']) }}
/>
