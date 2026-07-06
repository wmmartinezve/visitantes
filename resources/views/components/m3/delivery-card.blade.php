@props(['item', 'cantidad', 'invitado', 'refugio' => null, 'direccion' => null, 'distanciaKm' => null, 'rutaUrl' => null, 'refugioUrl' => null])

<div {{ $attributes->merge(['class' => 'm3-delivery-card']) }}>
    <x-m3.tricolor-bar class="!h-1" />

    <div class="p-4">
        <div class="flex items-start gap-3">
            <div class="m3-delivery-card-icon">
                <span class="material-symbols-outlined text-m3-on-tertiary-container">inventory_2</span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <p class="font-semibold text-m3-on-surface">{{ $item }}</p>
                    @if($distanciaKm !== null)
                        <span class="m3-distance-badge">
                            <span class="material-symbols-outlined text-sm">route</span>
                            {{ number_format((float) $distanciaKm, 1, ',', '.') }} km
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-m3-on-surface-variant">{{ $cantidad }} u. · {{ $invitado }}</p>
            </div>
        </div>

        @if($refugio)
            <p class="mt-3 flex items-start gap-1.5 text-xs text-m3-on-surface-variant">
                <span class="material-symbols-outlined text-sm text-m3-secondary">home_work</span>
                <span><strong class="font-medium text-m3-on-surface">Refugio:</strong> {{ $refugio }}</span>
            </p>
        @endif

        @if($direccion)
            <p class="mt-1 flex items-start gap-1.5 text-xs text-m3-on-surface-variant">
                <span class="material-symbols-outlined text-sm text-m3-secondary">location_on</span>
                <span>{{ $direccion }}</span>
            </p>
        @endif

        @if($rutaUrl)
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="{{ $rutaUrl }}" target="_blank" rel="noopener noreferrer" class="m3-btn-outlined flex items-center justify-center gap-1 text-sm">
                    <span class="material-symbols-outlined text-lg">directions</span>
                    Cómo llegar
                </a>
                @if($refugioUrl)
                    <a href="{{ $refugioUrl }}" target="_blank" rel="noopener noreferrer" class="m3-btn-outlined flex items-center justify-center gap-1 text-sm">
                        <span class="material-symbols-outlined text-lg">map</span>
                        Ver refugio
                    </a>
                @endif
            </div>
        @endif

        @isset($actions)
            <div class="mt-3">{{ $actions }}</div>
        @endisset
    </div>
</div>
