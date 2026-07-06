<x-filament-panels::page>
    @php($puntos = $this->puntos)

    <div class="space-y-4">
        <x-filament::section heading="Filtros territoriales">
            {{ $this->form }}
        </x-filament::section>

        <div
            wire:key="mapa-resumen-{{ md5(json_encode($this->data ?? [])) }}"
            class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-300"
        >
            <span class="inline-flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-blue-600"></span>
                Refugios ({{ count($puntos['refugios']) }})
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-emerald-600"></span>
                Centros de acopio ({{ count($puntos['centros']) }})
            </span>
            <span class="text-gray-500 dark:text-gray-400">
                {{ config('visitantes.estado') }}, {{ config('visitantes.pais') }}
            </span>
        </div>

        <div
            id="mapa-operacion"
            wire:ignore
            data-puntos='@json($puntos)'
            class="relative z-[1] w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-900"
            style="height: min(70vh, 640px); min-height: 420px;"
        ></div>
    </div>

    @assets
        <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
            crossorigin=""
        />
        <script
            src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"
            crossorigin=""
        ></script>
        <style>
            #mapa-operacion.leaflet-container {
                height: 100%;
                width: 100%;
                z-index: 1;
            }

            #mapa-operacion img,
            #mapa-operacion .leaflet-tile {
                max-width: none !important;
                max-height: none !important;
            }
        </style>
    @endassets

    @script
        <script>
            function readMapaPuntos(mapEl) {
                try {
                    return JSON.parse(mapEl?.dataset?.puntos ?? '{}');
                } catch {
                    return { refugios: [], centros: [] };
                }
            }

            function renderMapaOperacion(puntos) {
                const mapEl = document.getElementById('mapa-operacion');

                if (!mapEl || typeof L === 'undefined') {
                    return false;
                }

                if (mapEl._leaflet_map) {
                    mapEl._leaflet_map.remove();
                    mapEl._leaflet_map = null;
                }

                const map = L.map(mapEl, { scrollWheelZoom: true }).setView([9.85, -64.25], 8);
                mapEl._leaflet_map = map;

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                const bounds = [];

                const refIcon = L.divIcon({
                    className: '',
                    html: '<div style="background:#2563eb;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                });

                const centroIcon = L.divIcon({
                    className: '',
                    html: '<div style="background:#059669;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                });

                (puntos?.refugios ?? []).forEach((r) => {
                    if (r.lat == null || r.lng == null) {
                        return;
                    }

                    L.marker([r.lat, r.lng], { icon: refIcon })
                        .addTo(map)
                        .bindPopup(
                            `<strong>${r.nombre}</strong><br>` +
                            `${r.parroquia}, ${r.municipio}<br>` +
                            `Invitados: ${r.invitados}`
                        );
                    bounds.push([r.lat, r.lng]);
                });

                (puntos?.centros ?? []).forEach((c) => {
                    if (!c.activo || c.lat == null || c.lng == null) {
                        return;
                    }

                    L.marker([c.lat, c.lng], { icon: centroIcon })
                        .addTo(map)
                        .bindPopup(
                            `<strong>${c.nombre}</strong><br>` +
                            `${c.parroquia}, ${c.municipio}<br>` +
                            `Centro de acopio`
                        );
                    bounds.push([c.lat, c.lng]);
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 11 });
                } else {
                    map.setView([9.85, -64.25], 8);
                }

                [50, 150, 400, 800].forEach((delay) => {
                    setTimeout(() => map.invalidateSize(), delay);
                });

                return true;
            }

            function bootMapaOperacion(attempt = 0) {
                const mapEl = document.getElementById('mapa-operacion');

                if (typeof L !== 'undefined' && mapEl) {
                    renderMapaOperacion(readMapaPuntos(mapEl));
                    return;
                }

                if (attempt < 120) {
                    setTimeout(() => bootMapaOperacion(attempt + 1), 100);
                }
            }

            bootMapaOperacion();

            document.addEventListener('livewire:navigated', () => bootMapaOperacion());

            $wire.on('refresh-mapa-operacion', (...args) => {
                const payload = args[0] ?? {};
                const puntos = payload.puntos ?? payload;
                const mapEl = document.getElementById('mapa-operacion');

                if (mapEl && puntos?.refugios) {
                    mapEl.dataset.puntos = JSON.stringify(puntos);
                }

                if (!renderMapaOperacion(puntos)) {
                    bootMapaOperacion();
                }
            });
        </script>
    @endscript
</x-filament-panels::page>
