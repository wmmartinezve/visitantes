@props(['href', 'nombre', 'subtitulo', 'initial' => '?', 'foto' => null, 'estatus' => null, 'estatusLabel' => null])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'm3-guest-card']) }}>
    <div class="m3-guest-card-accent" aria-hidden="true">
        <span class="ve-yellow"></span>
        <span class="ve-blue"></span>
        <span class="ve-red"></span>
    </div>
    <div class="flex min-w-0 flex-1 items-center gap-3 p-3">
        @if($foto)
            <img src="{{ $foto }}" alt="" class="h-12 w-12 shrink-0 rounded-full object-cover ring-2 ring-m3-primary/20">
        @else
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-m3-primary-container text-sm font-bold text-m3-primary">
                {{ $initial }}
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-m3-on-surface">{{ $nombre }}</p>
            <p class="truncate text-xs text-m3-on-surface-variant">{{ $subtitulo }}</p>
        </div>
        @if($estatus && $estatusLabel)
            <x-m3.status-chip :estatus="$estatus" :label="$estatusLabel" />
        @endif
        <span class="material-symbols-outlined shrink-0 text-m3-primary">chevron_right</span>
    </div>
</a>
