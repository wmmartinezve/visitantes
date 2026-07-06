<x-layouts.m3-base title="Visitantes — Sistema de Asistencia">
    <div class="ve-tricolor-bar" aria-hidden="true">
        <span class="ve-yellow"></span>
        <span class="ve-blue"></span>
        <span class="ve-red"></span>
    </div>

    <div class="mx-auto flex min-h-[calc(100vh-6px)] max-w-lg flex-col px-4 py-10">
        <div class="mb-8 text-center">
            <x-visitantes-logo class="mx-auto mb-4 ring-2 ring-m3-primary" size="h-16 w-16" />
            <p class="text-xs font-medium uppercase tracking-wider text-m3-primary">Visitantes · {{ config('visitantes.estado') }}</p>
            <h1 class="mt-1 text-2xl font-medium text-m3-on-surface">Gestión de Invitados</h1>
            <p class="mt-2 text-sm text-m3-on-surface-variant">
                Sistema de asistencia y logística para anfitriones y centros de acopio en {{ config('visitantes.estado') }}.
            </p>
        </div>

        <div class="space-y-3">
            <a href="{{ url('/admin') }}" class="m3-list-item">
                <span class="material-symbols-outlined text-m3-primary">admin_panel_settings</span>
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-m3-on-surface">Panel administrativo</p>
                    <p class="text-xs text-m3-on-surface-variant">Catálogo, asignación de requerimientos, reportes (web)</p>
                </div>
                <span class="material-symbols-outlined text-m3-on-surface-variant">chevron_right</span>
            </a>

            <div class="m3-list-item opacity-90">
                <span class="material-symbols-outlined text-m3-secondary">phone_android</span>
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-m3-on-surface">App móvil Flutter</p>
                    <p class="text-xs text-m3-on-surface-variant">Anfitrión y Centro de Acopio — carpeta <code class="text-xs">mobile/</code></p>
                </div>
            </div>
        </div>

        <p class="mt-8 text-center text-xs text-m3-on-surface-variant">
            {{ config('visitantes.estado') }}, {{ config('visitantes.pais') }} · Operación de contingencia
        </p>

        @if (app()->environment('local'))
            <div class="mt-6 rounded-xl border border-m3-outline-variant bg-m3-surface-container p-4 text-xs text-m3-on-surface-variant">
                <p class="mb-2 font-medium text-m3-on-surface">Credenciales demo (local)</p>
                <p><span class="font-medium">Panel admin:</span> admin@visitantes.test / password</p>
                <p class="mt-1"><span class="font-medium">Anfitrión:</span> anfitrion@visitantes.test / password → app Flutter</p>
                <p class="mt-1"><span class="font-medium">Acopio:</span> acopio@visitantes.test / password → app Flutter</p>
            </div>
        @endif
    </div>

    <div class="ve-tricolor-bar fixed bottom-0 left-0 right-0" aria-hidden="true">
        <span class="ve-yellow"></span>
        <span class="ve-blue"></span>
        <span class="ve-red"></span>
    </div>
</x-layouts.m3-base>
