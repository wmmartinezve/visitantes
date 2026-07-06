@props(['icon', 'title', 'value', 'subtitle' => null, 'accent' => 'primary'])

@php
    $iconColors = match($accent) {
        'red' => 'bg-m3-secondary-container text-m3-secondary',
        'yellow' => 'bg-m3-tertiary-container text-m3-tertiary',
        'warning' => 'bg-m3-warning-container text-m3-warning',
        default => 'bg-m3-primary-container text-m3-primary',
    };
@endphp

<div {{ $attributes->merge(['class' => 'm3-stat-card']) }}>
    <div @class(['m3-stat-card-icon', $iconColors])>
        <span class="material-symbols-outlined text-[28px]">{{ $icon }}</span>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-xs text-m3-on-surface-variant">{{ $title }}</p>
        <p class="text-lg font-semibold text-m3-on-surface">{{ $value }}</p>
        @if($subtitle)
            <p class="text-xs text-m3-on-surface-variant">{{ $subtitle }}</p>
        @endif
    </div>
</div>
