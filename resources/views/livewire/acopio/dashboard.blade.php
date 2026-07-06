<div class="space-y-4">
    <x-m3.stat-card
        icon="warehouse"
        title="Centro de acopio"
        :value="$centro?->nombre ?? '—'"
    />

    <div class="grid grid-cols-2 gap-3">
        <x-m3.stat-card
            icon="inventory"
            title="Ítems en stock"
            :value="(string) $totalItems"
        />
        <x-m3.stat-card
            icon="warning"
            title="Stock bajo (≤5)"
            :value="(string) $stockBajo"
            :accent="$stockBajo > 0 ? 'red' : 'primary'"
        />
    </div>

    @if($entregasPendientes > 0)
        <x-m3.banner type="warning">
            Tienes {{ $entregasPendientes }} {{ $entregasPendientes === 1 ? 'entrega pendiente' : 'entregas pendientes' }}.
        </x-m3.banner>
    @endif

    <x-m3.section-header title="Acciones rápidas" />

    <div class="grid grid-cols-2 gap-3">
        <x-m3.quick-action href="{{ route('acopio.inventario') }}" icon="inventory_2" label="Inventario" />
        <x-m3.quick-action href="{{ route('acopio.requerimientos') }}" icon="local_shipping" label="Entregas" accent="red" />
    </div>
</div>
