@props(['icon', 'title', 'message' => null])

<div {{ $attributes->merge(['class' => 'm3-empty-state']) }}>
    <div class="m3-empty-state-icon">
        <span class="material-symbols-outlined text-4xl text-m3-primary">{{ $icon }}</span>
    </div>
    <p class="mt-4 text-base font-medium text-m3-on-surface">{{ $title }}</p>
    @if($message)
        <p class="mt-2 text-sm text-m3-on-surface-variant">{{ $message }}</p>
    @endif
</div>
