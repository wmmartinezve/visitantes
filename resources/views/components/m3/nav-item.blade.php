@props(['href', 'icon', 'label', 'active' => false, 'badge' => null])

<a href="{{ $href }}" @class(['m3-nav-item relative', 'active' => $active])>
    <span class="relative">
        <span class="material-symbols-outlined text-2xl">{{ $icon }}</span>
        @if($badge)
            <span class="absolute -right-2 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-m3-secondary px-1 text-[10px] font-bold text-white">{{ $badge }}</span>
        @endif
    </span>
    <span>{{ $label }}</span>
</a>
