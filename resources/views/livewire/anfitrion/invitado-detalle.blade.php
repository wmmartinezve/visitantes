<div class="space-y-4">
    @if (session('success'))
        <x-m3.banner type="success">{{ session('success') }}</x-m3.banner>
    @endif

    <a href="{{ route('anfitrion.invitados') }}" class="m3-btn-text !justify-start !px-0">
        <span class="material-symbols-outlined">arrow_back</span>
        Volver
    </a>

    <div class="m3-guest-card !pointer-events-none">
        <div class="m3-guest-card-accent" aria-hidden="true">
            <span class="ve-yellow"></span>
            <span class="ve-blue"></span>
            <span class="ve-red"></span>
        </div>
        <div class="flex gap-4 p-4">
            @if ($fotoUrl = $invitado->fotoDisplayUrl())
                <img src="{{ $fotoUrl }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-m3-primary/20">
            @else
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-m3-primary-container text-xl font-bold text-m3-primary">
                    {{ strtoupper(substr($invitado->nombre, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="text-xl font-semibold text-m3-on-surface">{{ $invitado->nombreCompleto() }}</h1>
                <p class="mt-1 flex items-center gap-1 text-sm text-m3-on-surface-variant">
                    <span class="material-symbols-outlined text-base">id_card</span>{{ $invitado->cedula ?: 'Sin cédula' }}
                </p>
                <p class="flex items-center gap-1 text-sm text-m3-on-surface-variant">
                    <span class="material-symbols-outlined text-base">phone</span>{{ $invitado->telefono ?: 'Sin teléfono' }}
                </p>
            </div>
        </div>
    </div>

    @if ($invitado->miembrosFamilia->isNotEmpty())
        <x-m3.section-header :title="'Núcleo familiar ('.$invitado->miembrosFamilia->count().')'" />
        <div class="space-y-2">
            @foreach ($invitado->miembrosFamilia as $familiar)
                <div class="m3-card !flex items-center gap-3 !p-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-m3-tertiary-container text-sm font-semibold text-m3-on-tertiary-container">
                        {{ strtoupper(substr($familiar->nombre, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-medium">{{ $familiar->nombreCompleto() }}</p>
                        <p class="text-xs text-m3-on-surface-variant">{{ $familiar->parentesco ?? 'Sin parentesco' }}@if($familiar->cedula) · {{ $familiar->cedula }}@endif</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
