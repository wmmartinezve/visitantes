@props([
    'label',
    'icon' => null,
    'type' => 'text',
    'error' => null,
    'hint' => null,
])

<div {{ $attributes->only('class')->merge(['class' => 'm3-field']) }}>
    @if($icon)
        <span class="material-symbols-outlined m3-field-icon">{{ $icon }}</span>
    @endif
    <input
        {{ $attributes->except('class')->merge([
            'type' => $type,
            'placeholder' => ' ',
            'class' => 'm3-input'.($icon ? '' : ' no-icon'),
        ]) }}
    />
    <label class="m3-label">{{ $label }}</label>
    @if($error)
        <p class="m3-error">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1 px-4 text-xs text-m3-on-surface-variant">{{ $hint }}</p>
    @endif
</div>
