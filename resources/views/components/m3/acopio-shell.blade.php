<x-m3.app-shell
    title="Visitantes — Acopio"
    subtitle="Centro de acopio · {{ config('visitantes.estado') }}"
    :user-name="auth()->user()->name"
    context-label="Centro"
    :context-value="auth()->user()->centroAcopio?->nombre"
    :logout-route="route('acopio.logout')"
    :offline-enabled="true"
>
    <x-slot:navigation>
        @include('partials.m3.acopio-nav')
    </x-slot:navigation>

    {{ $slot }}
</x-m3.app-shell>
