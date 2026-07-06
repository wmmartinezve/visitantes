@props(['label', 'icon', 'error' => null])

<div class="m3-field">
    <span class="material-symbols-outlined m3-field-icon">{{ $icon }}</span>
    <select {{ $attributes->merge(['class' => 'm3-select']) }}>
        {{ $slot }}
    </select>
    <label class="m3-label">{{ $label }}</label>
</div>
@if($error)
    <p class="m3-error">{{ $error }}</p>
@endif
