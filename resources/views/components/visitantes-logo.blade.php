@props([
    'size' => 'h-16 w-16',
])

<img
    src="{{ asset(config('visitantes.brand.logo')) }}"
    alt="Visitantes · {{ config('visitantes.estado') }}"
    {{ $attributes->class([$size, 'rounded-full object-cover']) }}
/>
