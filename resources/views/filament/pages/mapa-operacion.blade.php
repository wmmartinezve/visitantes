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

            .mapa-marker-wrap {
                background: transparent !important;
                border: none !important;
            }

            .mapa-marker {
                position: relative;
                width: 44px;
                height: 52px;
            }

            .mapa-marker__pin {
                position: absolute;
                top: 0;
                left: 50%;
                width: 38px;
                height: 38px;
                margin-left: -19px;
                border: 3px solid #fff;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.45);
            }

            .mapa-marker__icon {
                position: absolute;
                top: 7px;
                left: 50%;
                width: 24px;
                height: 24px;
                margin-left: -12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                pointer-events: none;
            }

            .mapa-marker__icon svg {
                width: 20px;
                height: 20px;
                filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.35));
            }

            .mapa-marker--refugio .mapa-marker__pin {
                background: linear-gradient(145deg, #2563eb, #002776);
            }

            .mapa-marker--centro .mapa-marker__pin {
                background: linear-gradient(145deg, #10b981, #047857);
            }

            /* Mantener capas Leaflet dentro del mapa; no tapar menús Filament (z-30+) */
            .mapa-operacion-contenedor {
                position: relative;
                z-index: 0;
                isolation: isolate;
            }

            #mapa-operacion.leaflet-container {
                z-index: 1 !important;
            }

            #mapa-operacion .leaflet-tile-pane { z-index: 1 !important; }
            #mapa-operacion .leaflet-overlay-pane { z-index: 2 !important; }
            #mapa-operacion .leaflet-shadow-pane { z-index: 3 !important; }
            #mapa-operacion .leaflet-marker-pane { z-index: 4 !important; }
            #mapa-operacion .leaflet-tooltip-pane { z-index: 5 !important; }
            #mapa-operacion .leaflet-popup-pane { z-index: 6 !important; }
            #mapa-operacion .leaflet-map-pane canvas { z-index: 1 !important; }
            #mapa-operacion .leaflet-map-pane svg { z-index: 2 !important; }
            #mapa-operacion .leaflet-control-container .leaflet-top,
            #mapa-operacion .leaflet-control-container .leaflet-bottom,
            #mapa-operacion .leaflet-control { z-index: 7 !important; }

            #mapa-operacion-estado {
                z-index: 8 !important;
            }
        </style>
    @endpush

    @push('scripts')
        @php($googleMapsKey = config('services.google_maps.api_key'))
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
        @if ($googleMapsKey)
            <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}"></script>
            <script src="{{ asset('vendor/leaflet/google-mutant.js') }}"></script>
        @endif
        <script>
            (function () {
                const GOOGLE_MAPS_KEY = @json($googleMapsKey);
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

                function googleMapsReady() {
                    return typeof google !== 'undefined' && typeof google.maps !== 'undefined';
                }

                function addTileLayer(map) {
                    if (
                        GOOGLE_MAPS_KEY
                        && typeof L !== 'undefined'
                        && L.gridLayer
                        && typeof L.gridLayer.googleMutant === 'function'
                        && googleMapsReady()
                    ) {
                        try {
                            return L.gridLayer.googleMutant({ type: 'roadmap', maxZoom: 21 }).addTo(map);
                        } catch (error) {
                            console.warn('[mapa-operacion] Google Maps no disponible', error);
                        }
                    }

                    for (const layer of TILE_LAYERS) {
                        try {
                            return L.tileLayer(layer.url, layer.options).addTo(map);
                        } catch (error) {
                            console.warn('[mapa-operacion] Capa base no disponible', error);
                        }
                    }

                    throw new Error('No hay capa de mapa disponible.');
                }

                function whenMapLibrariesReady(callback, attempt = 0) {
                    const leafletReady = typeof L !== 'undefined';
                    const googleReady = !GOOGLE_MAPS_KEY || googleMapsReady();

                    if (leafletReady && googleReady) {
                        callback();
                        return;
                    }

                    if (attempt < 100) {
                        setTimeout(() => whenMapLibrariesReady(callback, attempt + 1), 100);
                        return;
                    }

                    if (GOOGLE_MAPS_KEY && !googleMapsReady()) {
                        console.warn('[mapa-operacion] Google Maps no cargó a tiempo; usando capa alternativa.');
                    }

                    callback();
                }

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                function markerIcon(tipo) {
                    const isRefugio = tipo === 'refugio';
                    const iconSvg = isRefugio
                        ? '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10v11h7v-6h4v6h7V10L12 3z"/></svg>'
                        : '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 8h-3V4H7v4H4c-1.1 0-2 .9-2 2v9h20v-9c0-1.1-.9-2-2-2zm-5 0H9V6h6v2z"/></svg>';

                    return L.divIcon({
                        className: 'mapa-marker-wrap',
                        html:
                            `<div class="mapa-marker mapa-marker--${isRefugio ? 'refugio' : 'centro'}">` +
                            '<div class="mapa-marker__pin"></div>' +
                            `<div class="mapa-marker__icon">${iconSvg}</div>` +
                            '</div>',
                        iconSize: [44, 52],
                        iconAnchor: [22, 48],
                        popupAnchor: [0, -46],
                    });
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
                    const refIcon = markerIcon('refugio');
                    const centroIcon = markerIcon('centro');

                    (puntos?.refugios ?? []).forEach((r) => {
                        if (r.lat == null || r.lng == null) {
                            return;
                        }

                        L.marker([r.lat, r.lng], { icon: refIcon })
                            .addTo(map)
                            .bindPopup(
                                `<strong>${escapeHtml(r.nombre)}</strong><br>` +
                                `${escapeHtml(r.parroquia)}, ${escapeHtml(r.municipio)}<br>` +
                                `Invitados: ${escapeHtml(r.invitados)}`
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
                                `<strong>${escapeHtml(c.nombre)}</strong><br>` +
                                `${escapeHtml(c.parroquia)}, ${escapeHtml(c.municipio)}<br>` +
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

                function bootMapaOperacion() {
                    whenMapLibrariesReady(() => {
                        if (!document.getElementById('mapa-operacion')) {
                            setMapaEstado('No se pudo cargar la librería del mapa.');
                            return;
                        }

                        try {
                            if (!renderMapaOperacion(readMapaPuntos())) {
                                throw new Error('Contenedor del mapa no disponible.');
                            }
                        } catch (error) {
                            console.error('[mapa-operacion]', error);
                            setMapaEstado('No se pudo inicializar el mapa. Recargue la página.');
                        }
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

        <div class="mapa-operacion-contenedor relative w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700" style="height: 640px; min-height: 420px;">
            <div
                id="mapa-operacion-estado"
                class="pointer-events-none absolute inset-0 flex items-center justify-center bg-gray-100 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300"
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
