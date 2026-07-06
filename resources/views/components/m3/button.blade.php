@props(['icon' => null, 'variant' => 'filled'])

@if($variant === 'filled')
    <button {{ $attributes->merge(['type' => 'submit', 'class' => 'm3-btn-filled w-full']) }}>
        @if($icon)<span class="material-symbols-outlined text-xl">{{ $icon }}</span>@endif
        {{ $slot }}
    </button>
@elseif($variant === 'danger')
    <button {{ $attributes->merge(['type' => 'submit', 'class' => 'm3-btn-danger w-full']) }}>
        @if($icon)<span class="material-symbols-outlined text-xl">{{ $icon }}</span>@endif
        {{ $slot }}
    </button>
@elseif($variant === 'tonal')
    <button {{ $attributes->merge(['type' => 'button', 'class' => 'm3-btn-tonal w-full']) }}>
        @if($icon)<span class="material-symbols-outlined text-xl">{{ $icon }}</span>@endif
        {{ $slot }}
    </button>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => 'm3-btn-outlined']) }}>
        @if($icon)<span class="material-symbols-outlined text-lg">{{ $icon }}</span>@endif
        {{ $slot }}
    </button>
@endif
