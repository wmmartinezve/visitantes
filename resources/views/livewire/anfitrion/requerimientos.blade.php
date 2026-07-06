<div class="space-y-4" wire:poll.30s>
    <x-m3.section-header title="Requerimientos">
        <x-slot:action>
            <span class="text-xs text-m3-on-surface-variant">Seguimiento de insumos</span>
        </x-slot:action>
    </x-m3.section-header>

    <div class="flex gap-2 overflow-x-auto pb-1">
        @foreach([
            'todos' => 'Todos',
            'pendiente' => 'Pendientes ('.$conteos['pendiente'].')',
            'asignado' => 'Asignados ('.$conteos['asignado'].')',
            'entregado' => 'Entregados ('.$conteos['entregado'].')',
        ] as $valor => $etiqueta)
            <button type="button"
                wire:click="$set('filtro', '{{ $valor }}')"
                @class(['m3-filter-chip', 'active' => $filtro === $valor])>
                {{ $etiqueta }}
            </button>
        @endforeach
    </div>

    <div class="space-y-2">
        @forelse($requerimientos as $req)
            <x-m3.requirement-card
                wire:key="req-{{ $req->id }}"
                :item="$req->item_solicitado"
                :cantidad="$req->cantidad"
                :invitado="$req->invitado?->nombreCompleto() ?? 'Invitado'"
                :estatus="$req->estatus?->value"
                :estatus-label="$req->estatus?->label() ?? '—'"
                :centro="$req->estatus?->value === 'asignado' && $req->centroAcopio ? $req->centroAcopio->nombre : null"
            />
        @empty
            <x-m3.empty-state
                icon="inventory_2"
                :title="$filtro === 'todos' ? 'No hay requerimientos registrados' : 'No hay requerimientos en este estado'"
            />
        @endforelse
    </div>
</div>
