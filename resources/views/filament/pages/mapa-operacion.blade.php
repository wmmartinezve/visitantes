<x-filament-panels::page>
    @php($puntos = $this->puntos)

    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
        <style>
            #mapa-operacion.leaflet-container {
                height: 100% !important;
                width: 100% !important;
                background: #e5e7eb;
            }

            .dark #mapa-operacion.leaflet-container {
                background: #111827;
            }

            #mapa-operacion img,
            #mapa-operacion .leaflet-tile {
                max-width: none !important;
                max-height: none !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
        <script>
            (function () {
                const TILE_LAYERS = [
                    {
                        url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
                        options: {
                            maxZoom: 19,
                            subdomains: 'abcd',
                            attribution: '&copy; OpenStreetMap &copy; CARTO',
                        },
                    },
                    {
                        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
                        options: {
                            maxZoom: 19,
                            attribution: '&copy; Esri',
                        },
                    },
                ];

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
                        estado.style.display = 'flex';
                    } else {
                        estado.style.display = 'none';
                    }
                }

                function addTileLayer(map) {
                    for (const layer of TILE_LAYERS) {
                        try {
                            return L.tileLayer(layer.url, layer.options).addTo(map);
                        } catch (error) {
                            console.warn('[mapa-operacion] Capa base no disponible', error);
                        }
                    }

                    throw new Error('No hay capa de mapa disponible.');
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

                    addTileLayer(map);

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

                    requestAnimationFrame(() => map.invalidateSize());
                    [100, 300, 800].forEach((delay) => setTimeout(() => map.invalidateSize(), delay));

                    setMapaEstado(null);

                    return true;
                }

                function bootMapaOperacion(attempt = 0) {
                    if (typeof L !== 'undefined' && document.getElementById('mapa-operacion')) {
                        try {
                            if (!renderMapaOperacion(readMapaPuntos())) {
                                throw new Error('Contenedor del mapa no disponible.');
                            }
                        } catch (error) {
                            console.error('[mapa-operacion]', error);
                            setMapaEstado('No se pudo inicializar el mapa. Recargue la página.');
                        }

                        return;
                    }

                    if (attempt < 80) {
                        setTimeout(() => bootMapaOperacion(attempt + 1), 100);
                        return;
                    }

                    setMapaEstado('No se pudo cargar la librería del mapa.');
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
                    if (window.__mapaOperacionListenersRegistered || !window.Livewire) {
                        return;
                    }

                    window.__mapaOperacionListenersRegistered = true;

                    Livewire.on('refresh-mapa-operacion', (event) => {
                        const puntos = extractPuntosFromEvent(event);

                        if (!puntos?.refugios) {
                            return;
                        }

                        writeMapaPuntos(puntos);
                        renderMapaOperacion(puntos);
                    });
                }

                bootMapaOperacion();
                document.addEventListener('livewire:navigated', () => bootMapaOperacion());
                document.addEventListener('livewire:init', registerLivewireListeners, { once: true });
                registerLivewireListeners();
            })();
        </script>
    @endpush

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

        <div class="relative w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700" style="height: 640px; min-height: 420px;">
            <div
                id="mapa-operacion-estado"
                class="pointer-events-none absolute inset-0 z-[500] flex items-center justify-center bg-gray-100 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300"
            >
                Cargando mapa…
            </div>

            <div
                id="mapa-operacion"
                wire:ignore
                class="h-full w-full"
                role="region"
                aria-label="Mapa operativo de refugios y centros de acopio"
            ></div>
        </div>
    </div>
</x-filament-panels::page>
