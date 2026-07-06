@props(['href', 'icon', 'label', 'accent' => 'primary'])

@php
    $iconColors = match($accent) {
        'red' => 'bg-m3-secondary/10 text-m3-secondary',
        'yellow' => 'bg-m3-tertiary-container text-m3-tertiary',
        default => 'bg-m3-primary/10 text-m3-primary',
    };
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'm3-quick-action']) }}>
    <div @class(['m3-quick-action-icon', $iconColors])>
        <span class="material-symbols-outlined">{{ $icon }}</span>
    </div>
    <span class="text-center text-xs font-medium text-m3-on-surface">{{ $label }}</span>
</a>
