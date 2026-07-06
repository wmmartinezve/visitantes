<div
    x-data="{
        loading: false,
        error: null,
        fijarUbicacion() {
            if (! navigator.geolocation) {
                this.error = 'Tu navegador no soporta geolocalización.';
                return;
            }
            this.loading = true;
            this.error = null;
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude.toFixed(8);
                    const lng = position.coords.longitude.toFixed(8);
                    $wire.set('data.latitud', lat, false);
                    $wire.set('data.longitud', lng, false);
                    this.loading = false;
                },
                (err) => {
                    this.loading = false;
                    this.error = err.code === 1
                        ? 'Permiso de ubicación denegado.'
                        : 'No se pudo obtener la ubicación.';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        },
    }"
    class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50"
>
    <div class="flex flex-wrap items-center gap-3">
        <button
            type="button"
            x-on:click="fijarUbicacion()"
            x-bind:disabled="loading"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-500 disabled:opacity-50 dark:bg-primary-500"
        >
            <span x-text="loading ? 'Obteniendo GPS…' : 'Fijar ubicación GPS'"></span>
        </button>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Usa la ubicación actual del dispositivo para llenar latitud y longitud en {{ config('visitantes.estado') }}.
        </p>
    </div>
    <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-danger-600 dark:text-danger-400"></p>
</div>
