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

        <script type="application/json" id="mapa-operacion-puntos">@json($puntos)</script>

        <div
            id="mapa-operacion"
            wire:ignore
            class="relative isolate w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-900"
            style="height: 640px; min-height: 420px;"
            role="region"
            aria-label="Mapa operativo de refugios y centros de acopio"
        >
            <div id="mapa-operacion-estado" class="absolute inset-0 flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                Cargando mapa…
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <style>
            #mapa-operacion.leaflet-container {
                height: 100% !important;
                width: 100% !important;
                z-index: 0;
            }

            #mapa-operacion .leaflet-pane,
            #mapa-operacion .leaflet-top,
            #mapa-operacion .leaflet-bottom {
                z-index: 1;
            }

            #mapa-operacion img,
            #mapa-operacion .leaflet-tile {
                max-width: none !important;
                max-height: none !important;
            }

            #mapa-operacion-estado {
                z-index: 0;
                pointer-events: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const LEAFLET_JS = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js';
                const LEAFLET_CSS = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css';

                function readMapaPuntos() {
                    const jsonEl = document.getElementById('mapa-operacion-puntos');

                    if (!jsonEl) {
                        return { refugios: [], centros: [] };
                    }

                    try {
                        return JSON.parse(jsonEl.textContent || '{}');
                    } catch {
                        return { refugios: [], centros: [] };
                    }
                }

                function writeMapaPuntos(puntos) {
                    const jsonEl = document.getElementById('mapa-operacion-puntos');

                    if (jsonEl) {
                        jsonEl.textContent = JSON.stringify(puntos);
                    }
                }

                function setMapaEstado(mensaje) {
                    const estado = document.getElementById('mapa-operacion-estado');

                    if (!estado) {
                        return;
                    }

                    if (mensaje) {
                        estado.textContent = mensaje;
                        estado.classList.remove('hidden');
                    } else {
                        estado.classList.add('hidden');
                    }
                }

                function loadStylesheet(href) {
                    if (document.querySelector(`link[href="${href}"]`)) {
                        return Promise.resolve();
                    }

                    return new Promise((resolve, reject) => {
                        const link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = href;
                        link.crossOrigin = '';
                        link.onload = () => resolve();
                        link.onerror = () => reject(new Error('No se pudo cargar Leaflet CSS.'));
                        document.head.appendChild(link);
                    });
                }

                function loadScript(src) {
                    if (document.querySelector(`script[src="${src}"]`)) {
                        return Promise.resolve();
                    }

                    return new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = src;
                        script.crossOrigin = '';
                        script.onload = () => resolve();
                        script.onerror = () => reject(new Error('No se pudo cargar Leaflet JS.'));
                        document.head.appendChild(script);
                    });
                }

                function ensureLeaflet() {
                    return loadStylesheet(LEAFLET_CSS).then(() => loadScript(LEAFLET_JS));
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

                    [50, 200, 500, 1000].forEach((delay) => {
                        setTimeout(() => map.invalidateSize(), delay);
                    });

                    setMapaEstado(null);

                    return true;
                }

                function bootMapaOperacion() {
                    setMapaEstado('Cargando mapa…');

                    ensureLeaflet()
                        .then(() => {
                            if (!renderMapaOperacion(readMapaPuntos())) {
                                throw new Error('Contenedor del mapa no disponible.');
                            }
                        })
                        .catch((error) => {
                            console.error('[mapa-operacion]', error);
                            setMapaEstado('No se pudo cargar el mapa. Revise su conexión e intente recargar la página.');
                        });
                }

                function extractPuntosFromEvent(event) {
                    if (event?.puntos) {
                        return event.puntos;
                    }

                    if (Array.isArray(event) && event[0]?.puntos) {
                        return event[0].puntos;
                    }

                    return event;
                }

                function registerLivewireListeners() {
                    if (window.__mapaOperacionListenersRegistered) {
                        return;
                    }

                    window.__mapaOperacionListenersRegistered = true;

                    Livewire.on('refresh-mapa-operacion', (event) => {
                        const puntos = extractPuntosFromEvent(event);

                        if (!puntos?.refugios) {
                            return;
                        }

                        writeMapaPuntos(puntos);

                        ensureLeaflet()
                            .then(() => renderMapaOperacion(puntos))
                            .catch((error) => console.error('[mapa-operacion]', error));
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bootMapaOperacion, { once: true });
                } else {
                    bootMapaOperacion();
                }

                document.addEventListener('livewire:navigated', bootMapaOperacion);

                document.addEventListener('livewire:init', registerLivewireListeners, { once: true });

                if (window.Livewire) {
                    registerLivewireListeners();
                }
            })();
        </script>
    @endpush
</x-filament-panels::page>
