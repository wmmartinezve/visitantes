@php
    use App\Enums\RequerimientoEstatus;
    use App\Models\Requerimiento;

    $current = request()->route()?->getName();
    $reqPendientes = auth()->user()?->refugio_id
        ? Requerimiento::query()
            ->whereHas('invitado', fn ($q) => $q->where('refugio_id', auth()->user()->refugio_id))
            ->where('estatus', RequerimientoEstatus::Pendiente)
            ->count()
        : 0;
@endphp

<x-m3.nav-item href="{{ route('anfitrion.dashboard') }}" icon="home" label="Inicio"
    :active="str_starts_with((string) $current, 'anfitrion.dashboard')" />
<x-m3.nav-item href="{{ route('anfitrion.registrar') }}" icon="person_add" label="Registrar"
    :active="str_starts_with((string) $current, 'anfitrion.registrar')" />
<x-m3.nav-item href="{{ route('anfitrion.invitados') }}" icon="groups" label="Invitados"
    :active="str_starts_with((string) $current, 'anfitrion.invitados') || str_starts_with((string) $current, 'anfitrion.invitado')" />
<x-m3.nav-item href="{{ route('anfitrion.requerimientos') }}" icon="inventory_2" label="Req."
    :active="str_starts_with((string) $current, 'anfitrion.requerimientos')"
    :badge="$reqPendientes > 0 ? $reqPendientes : null" />
