@php
    use App\Enums\RequerimientoEstatus;
    use App\Models\Requerimiento;

    $current = request()->route()?->getName();
    $entregasPendientes = auth()->user()?->centro_acopio_id
        ? Requerimiento::query()
            ->where('centro_acopio_id', auth()->user()->centro_acopio_id)
            ->where('estatus', RequerimientoEstatus::Asignado)
            ->count()
        : 0;
@endphp

<x-m3.nav-item href="{{ route('acopio.dashboard') }}" icon="home" label="Inicio"
    :active="str_starts_with((string) $current, 'acopio.dashboard')" />
<x-m3.nav-item href="{{ route('acopio.inventario') }}" icon="inventory_2" label="Inventario"
    :active="str_starts_with((string) $current, 'acopio.inventario')" />
<x-m3.nav-item href="{{ route('acopio.requerimientos') }}" icon="local_shipping" label="Entregas"
    :active="str_starts_with((string) $current, 'acopio.requerimientos')"
    :badge="$entregasPendientes > 0 ? $entregasPendientes : null" />
