@props(['estatus', 'label'])

@php
    $classes = match($estatus) {
        'pendiente' => 'bg-m3-tertiary-container text-m3-on-tertiary-container',
        'asignado' => 'bg-m3-primary-container text-m3-on-primary-container',
        'entregado' => 'bg-m3-success-container text-m3-success',
        'activo' => 'bg-m3-success-container text-m3-success',
        default => 'bg-m3-surface-container text-m3-on-surface-variant',
    };
@endphp

<span {{ $attributes->merge(['class' => "m3-chip shrink-0 {$classes}"]) }}>{{ $label }}</span>
