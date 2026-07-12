@php
    $user = auth()->user();
    $current = request()->route()?->getName();
    $tieneHogar = $user?->hogar_solidario_id !== null;
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
@endif
