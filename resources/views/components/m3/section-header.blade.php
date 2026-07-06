@props(['title'])

<div {{ $attributes->merge(['class' => 'm3-section-header']) }}>
    <span class="m3-section-header-accent" aria-hidden="true"></span>
    <h2 class="flex-1 text-base font-semibold text-m3-on-surface">{{ $title }}</h2>
    @if(isset($action))
        <div>{{ $action }}</div>
    @endif
</div>
