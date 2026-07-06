@props(['type' => 'info'])

@php
    $classes = match($type) {
        'success' => 'bg-m3-success-container text-m3-success',
        'warning' => 'bg-m3-warning-container text-m3-warning',
        'error' => 'bg-m3-error-container text-m3-error',
        default => 'bg-m3-primary-container text-m3-on-primary-container',
    };
@endphp

<div {{ $attributes->merge(['class' => "m3-snackbar {$classes}"]) }}>
    {{ $slot }}
</div>
