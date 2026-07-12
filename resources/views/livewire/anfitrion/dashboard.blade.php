<div class="space-y-4">
    <x-m3.stat-card
        icon="home_work"
        title="Refugio asignado"
        :value="$refugio?->nombre ?? '—'"
    />

    <x-m3.stat-card
        icon="groups"
        title="Invitados activos"
        :value="(string) $invitadosActivos"
        accent="primary"
    />

    <x-m3.section-header title="Acciones rápidas" />

    <div class="grid grid-cols-2 gap-3">
        <x-m3.quick-action href="{{ route('anfitrion.registrar') }}" icon="person_add" label="Registrar Invitado" accent="red" />
        <x-m3.quick-action href="{{ route('anfitrion.invitados') }}" icon="groups" label="Ver Invitados" />
    </div>
</div>
