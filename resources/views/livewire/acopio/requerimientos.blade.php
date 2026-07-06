<div class="space-y-4" wire:poll.30s>
    <x-m3.section-header title="Entregas">
        <x-slot:action>
            <span class="text-xs text-m3-on-surface-variant">Por distancia al refugio</span>
        </x-slot:action>
    </x-m3.section-header>

    @if($alertaNuevaEntrega)
        <x-m3.banner type="warning">Nueva entrega asignada a tu centro.</x-m3.banner>
    @endif

    @if($mensaje)
        <x-m3.banner type="success">{{ $mensaje }}</x-m3.banner>
    @endif

    @if($error)
        <x-m3.banner type="error">{{ $error }}</x-m3.banner>
    @endif

    <div class="space-y-3">
        @if($asignadosConRuta->isNotEmpty())
            <p class="text-sm font-medium text-m3-on-surface">{{ $asignadosConRuta->count() }} entrega(s) pendiente(s)</p>
        @endif

        @forelse($asignadosConRuta as $fila)
            @php($req = $fila['requerimiento'])
            <x-m3.delivery-card
                wire:key="req-{{ $req->id }}"
                :item="$req->item_solicitado"
                :cantidad="$req->cantidad"
                :invitado="$req->invitado?->nombreCompleto() ?? '—'"
                :refugio="$req->invitado?->refugio?->nombre"
                :direccion="$fila['direccion']"
                :distancia-km="$fila['distancia_km']"
                :ruta-url="$fila['ruta_url']"
                :refugio-url="$fila['refugio_url']"
            >
                <x-slot:actions>
                    <button type="button"
                        wire:click="marcarEntregado({{ $req->id }})"
                        wire:confirm="¿Confirmar entrega y descontar del inventario?"
                        wire:loading.attr="disabled"
                        wire:target="marcarEntregado({{ $req->id }})"
                        class="m3-btn-danger flex w-full">
                        <span class="material-symbols-outlined">done_all</span>
                        <span wire:loading.remove wire:target="marcarEntregado({{ $req->id }})">Marcar entregado</span>
                        <span wire:loading wire:target="marcarEntregado({{ $req->id }})">Procesando…</span>
                    </button>
                </x-slot:actions>
            </x-m3.delivery-card>
        @empty
            <x-m3.empty-state
                icon="local_shipping"
                title="No hay entregas pendientes"
                message="Cuando se asignen requerimientos a su centro, aparecerán aquí."
            />
        @endforelse
    </div>

    @if($entregados->isNotEmpty())
        <x-m3.section-header title="Entregas recientes" />
        <div class="space-y-2">
            @foreach($entregados as $req)
                <div class="m3-card !flex items-center gap-2 !p-3 opacity-80" wire:key="done-{{ $req->id }}">
                    <span class="material-symbols-outlined text-lg text-m3-success">check_circle</span>
                    <span class="text-sm font-medium text-m3-on-surface">{{ $req->item_solicitado }}</span>
                    <span class="text-sm text-m3-on-surface-variant">→ {{ $req->invitado?->nombreCompleto() }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
