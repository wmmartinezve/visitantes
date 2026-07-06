<div class="space-y-4">
    <x-m3.stat-card
        icon="home_work"
        title="Refugio asignado"
        :value="$refugio?->nombre ?? '—'"
    />

    <div class="grid grid-cols-2 gap-3">
        <x-m3.stat-card
            icon="groups"
            title="Invitados activos"
            :value="(string) $invitadosActivos"
            accent="primary"
        />
        <x-m3.stat-card
            icon="pending_actions"
            title="Req. pendientes"
            :value="(string) $requerimientosPendientes"
            :accent="$requerimientosPendientes > 0 ? 'yellow' : 'primary'"
        />
    </div>

    <x-m3.section-header title="Acciones rápidas" />

    <div class="grid grid-cols-2 gap-3">
        <x-m3.quick-action href="{{ route('anfitrion.registrar') }}" icon="person_add" label="Registrar Invitado" accent="red" />
        <x-m3.quick-action href="{{ route('anfitrion.invitados') }}" icon="groups" label="Ver Invitados" />
        <x-m3.quick-action href="{{ route('anfitrion.requerimientos') }}" icon="inventory_2" label="Requerimientos" class="col-span-2" />
    </div>
</div>
