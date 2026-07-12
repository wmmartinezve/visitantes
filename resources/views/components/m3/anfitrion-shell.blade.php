<x-m3.app-shell
    title="Visitantes — Anfitrión"
    subtitle="Anfitrión · {{ config('visitantes.estado') }}"
    :user-name="auth()->user()->name"
    context-label="Hogar solidario"
    :context-value="auth()->user()->refugio?->nombre ?? 'Pendiente de registro'"
    :logout-route="route('anfitrion.logout')"
    :profile-route="route('anfitrion.perfil')"
    :offline-enabled="true"
>
    <x-slot:navigation>
        @include('partials.m3.anfitrion-nav')
    </x-slot:navigation>

    {{ $slot }}
</x-m3.app-shell>
