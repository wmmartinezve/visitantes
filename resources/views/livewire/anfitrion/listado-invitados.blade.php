<div class="space-y-4">
    <x-m3.section-header title="Invitados">
        <x-slot:action>
            <span class="text-xs text-m3-on-surface-variant">Jefes de familia</span>
        </x-slot:action>
    </x-m3.section-header>

    <div class="m3-search">
        <span class="material-symbols-outlined text-m3-primary">search</span>
        <input type="search" wire:model.live.debounce.300ms="busqueda" placeholder="Nombre, apellido o cédula">
        <span class="material-symbols-outlined text-m3-secondary">arrow_forward</span>
    </div>

    @if($invitados->isNotEmpty())
        <p class="text-xs text-m3-on-surface-variant">{{ $invitados->total() }} invitado(s)</p>
    @endif

    <div class="space-y-2">
        @forelse ($invitados as $invitado)
            <x-m3.guest-card
                wire:key="inv-{{ $invitado->id }}"
                href="{{ route('anfitrion.invitado', $invitado) }}"
                :nombre="$invitado->nombreCompleto()"
                :subtitulo="($invitado->cedula ?: 'Sin cédula') . ' · ' . $invitado->miembrosFamilia->count() . ' familiar(es)'"
                :initial="strtoupper(substr($invitado->nombre, 0, 1))"
                :foto="$invitado->foto_ingreso ? asset('storage/'.$invitado->foto_ingreso) : null"
                :estatus="$invitado->estatus?->value"
                :estatus-label="$invitado->estatus?->label()"
            />
        @empty
            <x-m3.empty-state
                icon="groups_outlined"
                title="No hay Invitados en este refugio"
                message="Use Registrar para agregar el primer Invitado."
            />
        @endforelse
    </div>

    <div class="pb-4">{{ $invitados->links() }}</div>
</div>
