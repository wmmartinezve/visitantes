@php
    use App\Enums\RequerimientoEstatus;
    use App\Models\Requerimiento;

    $user = auth()->user();
    $current = request()->route()?->getName();
    $tieneHogar = $user?->hogar_solidario_id !== null;

    $reqPendientes = $tieneHogar
        ? Requerimiento::query()
            ->whereHas('invitado', fn ($q) => $q->where('hogar_solidario_id', $user->hogar_solidario_id))
            ->where('estatus', RequerimientoEstatus::Pendiente)
            ->count()
        : 0;
@endphp

@if ($tieneHogar)
    <x-m3.nav-item href="{{ route('anfitrion.dashboard') }}" icon="home" label="Inicio"
        :active="str_starts_with((string) $current, 'anfitrion.dashboard')" />
@endif

<x-m3.nav-item href="{{ route('anfitrion.registrar') }}" icon="person_add" label="Registrar"
    :active="str_starts_with((string) $current, 'anfitrion.registrar')" />

@if ($tieneHogar)
    <x-m3.nav-item href="{{ route('anfitrion.invitados') }}" icon="groups" label="Invitados"
        :active="str_starts_with((string) $current, 'anfitrion.invitados') || str_starts_with((string) $current, 'anfitrion.invitado')" />
    <x-m3.nav-item href="{{ route('anfitrion.requerimientos') }}" icon="inventory_2" label="Req."
        :active="str_starts_with((string) $current, 'anfitrion.requerimientos')"
        :badge="$reqPendientes > 0 ? $reqPendientes : null" />
@endif
