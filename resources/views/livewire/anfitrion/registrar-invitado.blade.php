<div class="space-y-4">
    @if ($this->requiereRegistroHogar)
        <x-m3.banner type="info">
            <p class="text-sm font-medium">Primero registre su hogar solidario y luego el núcleo familiar hospedado.</p>
            <p class="text-xs opacity-80">Un hogar solidario = un núcleo familiar.</p>
        </x-m3.banner>
    @elseif ($refugio)
        <x-m3.banner type="info" class="flex items-center gap-2">
            <span class="material-symbols-outlined">location_on</span>
            <div>
                <p class="text-xs opacity-80">Hogar solidario</p>
                <p class="font-medium">{{ $refugio->nombre }}</p>
            </div>
        </x-m3.banner>
    @endif

    <div class="m3-card space-y-2 !p-4">
        <p class="text-sm font-semibold text-m3-on-surface">
            Paso {{ $paso + 1 }} de {{ $this->totalPasos }} · {{ $this->titulosPasos[$paso] }}
        </p>
        <div class="h-2 overflow-hidden rounded-full bg-m3-surface-container">
            <div
                class="h-full rounded-full bg-m3-primary transition-all duration-300"
                style="width: {{ (($paso + 1) / $this->totalPasos) * 100 }}%"
            ></div>
        </div>
    </div>

    <form wire:submit="guardar" data-offline-form data-offline-type="invitado.registro" class="space-y-4">
        {{-- Paso: Hogar solidario --}}
        @if ($this->requiereRegistroHogar && $paso === 0)
            <section class="m3-card space-y-4">
                <h2 class="flex items-center gap-2 text-base font-semibold text-m3-on-surface">
                    <span class="material-symbols-outlined text-m3-primary">home_work</span>
                    Datos del hogar solidario
                </h2>

                <p class="text-xs text-m3-on-surface-variant mb-2">
                    El código del hogar se asignará automáticamente según municipio, parroquia y correlativo.
                </p>
                <x-m3.select-field label="Tipo de vivienda" icon="apartment" wire:model="hogar_tipo_vivienda" :error="$errors->first('hogar_tipo_vivienda')">
                    @foreach ($tiposVivienda as $tipo)
                        <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="¿Quién recibe al Invitado?" icon="group" wire:model.live="hogar_tipo_anfitrion" :error="$errors->first('hogar_tipo_anfitrion')">
                    @foreach ($tiposAnfitrion as $tipo)
                        <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                    @endforeach
                </x-m3.select-field>
                @if ($hogar_tipo_anfitrion === 'familiar')
                    <x-m3.select-field label="Parentesco con el jefe de familia" icon="family_restroom" wire:model="hogar_parentesco_anfitrion" :error="$errors->first('hogar_parentesco_anfitrion')" required>
                        <option value="">Seleccione…</option>
                        @foreach(config('visitantes.parentescos') as $parentesco)
                            <option value="{{ $parentesco }}">{{ $parentesco }}</option>
                        @endforeach
                    </x-m3.select-field>
                @endif
                <x-m3.select-field label="Estado" icon="public" wire:model="hogar_estado_id" :error="$errors->first('hogar_estado_id')">
                    @foreach ($estadosHogar as $estado)
                        <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="Municipio" icon="location_city" wire:model.live="hogar_municipio_id" :error="$errors->first('hogar_municipio_id')">
                    <option value="">Seleccione…</option>
                    @foreach ($municipiosHogar as $municipio)
                        <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="Parroquia" icon="place" wire:model.live="hogar_parroquia_id" :error="$errors->first('hogar_parroquia_id')">
                    <option value="">Seleccione…</option>
                    @foreach ($parroquiasHogar as $parroquia)
                        <option value="{{ $parroquia->id }}">{{ $parroquia->nombre }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="Comuna (opcional)" icon="map" wire:model="hogar_comuna_id" :error="$errors->first('hogar_comuna_id')">
                    <option value="">Sin comuna</option>
                    @foreach ($comunasHogar as $comuna)
                        <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.text-field label="Dirección exacta" icon="signpost" wire:model="hogar_direccion" :error="$errors->first('hogar_direccion')" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-m3.text-field label="Latitud" icon="my_location" type="number" step="any" wire:model="hogar_latitud" :error="$errors->first('hogar_latitud')" />
                    <x-m3.text-field label="Longitud" icon="my_location" type="number" step="any" wire:model="hogar_longitud" :error="$errors->first('hogar_longitud')" />
                </div>
                <p class="text-xs text-m3-on-surface-variant">Use el GPS del dispositivo o ingrese las coordenadas manualmente.</p>

                <hr class="border-m3-outline-variant/40">

                <x-m3.text-field label="Responsable del hogar" icon="person" wire:model="responsable_nombre" :error="$errors->first('responsable_nombre')" />
                <x-m3.text-field label="Cédula del responsable" icon="id_card" wire:model="responsable_cedula" />
                <x-m3.text-field label="Teléfono del responsable" icon="phone" type="tel" wire:model="responsable_telefono" />
            </section>
        @endif

        {{-- Paso: Jefe de familia --}}
        @php
            $pasoJefe = $this->requiereRegistroHogar ? 1 : 0;
        @endphp
        @if ($paso === $pasoJefe)
            <section class="m3-card space-y-4">
                <h2 class="flex items-center gap-2 text-base font-medium text-m3-on-surface">
                    <span class="material-symbols-outlined text-m3-primary">badge</span>
                    Datos del Invitado (jefe de familia)
                </h2>

                <x-m3.text-field label="Nombre" icon="person" wire:model="nombre" data-offline-field="nombre" :error="$errors->first('nombre')" />
                <x-m3.text-field label="Apellido" icon="person" wire:model="apellido" data-offline-field="apellido" :error="$errors->first('apellido')" />
                <x-m3.text-field label="Cédula" icon="id_card" wire:model="cedula" data-offline-field="cedula" />
                <x-m3.text-field label="Teléfono" icon="phone" type="tel" wire:model="telefono" data-offline-field="telefono" />
                <x-m3.text-field label="Fecha de nacimiento" icon="cake" type="date" wire:model="fecha_nacimiento" data-offline-field="fecha_nacimiento" :error="$errors->first('fecha_nacimiento')" />
            </section>

            <section class="m3-card space-y-4">
                <h2 class="flex items-center gap-2 text-base font-medium text-m3-on-surface">
                    <span class="material-symbols-outlined text-m3-primary">map</span>
                    Procedencia y situación laboral
                </h2>

                <x-m3.select-field label="Estado de procedencia" icon="public" wire:model.live="procedencia_estado_id" :error="$errors->first('procedencia_estado_id')">
                    <option value="">Seleccione…</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="Municipio de procedencia" icon="location_city" wire:model.live="procedencia_municipio_id" :error="$errors->first('procedencia_municipio_id')">
                    <option value="">Seleccione…</option>
                    @foreach ($municipiosProcedencia as $municipio)
                        <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="Parroquia de procedencia" icon="place" wire:model="procedencia_parroquia_id" :error="$errors->first('procedencia_parroquia_id')">
                    <option value="">Seleccione…</option>
                    @foreach ($parroquiasProcedencia as $parroquia)
                        <option value="{{ $parroquia->id }}">{{ $parroquia->nombre }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="Situación del jefe de familia" icon="work" wire:model="situacion_jefe" :error="$errors->first('situacion_jefe')">
                    <option value="">Seleccione…</option>
                    @foreach ($situacionesJefe as $situacion)
                        <option value="{{ $situacion->value }}">{{ $situacion->label() }}</option>
                    @endforeach
                </x-m3.select-field>
                <x-m3.select-field label="Condición" icon="accessibility_new" wire:model="condicion" :error="$errors->first('condicion')">
                    @foreach ($condiciones as $condicion)
                        <option value="{{ $condicion->value }}">{{ $condicion->label() }}</option>
                    @endforeach
                </x-m3.select-field>
            </section>
        @endif

        {{-- Paso: Familiares --}}
        @php
            $pasoFamiliares = $this->requiereRegistroHogar ? 2 : 1;
        @endphp
        @if ($paso === $pasoFamiliares)
            <section class="m3-card space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-base font-medium">
                        <span class="material-symbols-outlined text-m3-primary">family_restroom</span>
                        Núcleo familiar
                    </h2>
                    <button type="button" wire:click="agregarFamiliar" class="m3-btn-tonal !min-h-[40px] !px-4 !py-2 !text-xs">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Agregar
                    </button>
                </div>

                @forelse ($familiares as $index => $familiar)
                    <div wire:key="familiar-{{ $index }}" data-offline-familiar class="m3-card-filled space-y-3 !p-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium">Familiar {{ $index + 1 }}</p>
                            <button type="button" wire:click="quitarFamiliar({{ $index }})" class="m3-btn-text !min-h-0 !text-m3-error">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                        <x-m3.select-field
                            label="Parentesco"
                            icon="family_restroom"
                            wire:model="familiares.{{ $index }}.parentesco"
                            data-offline-field="parentesco"
                            :error="$errors->first('familiares.'.$index.'.parentesco')"
                            required
                        >
                            <option value="">Seleccione…</option>
                            @foreach(config('visitantes.parentescos') as $parentesco)
                                <option value="{{ $parentesco }}">{{ $parentesco }}</option>
                            @endforeach
                        </x-m3.select-field>
                        <x-m3.select-field
                            label="Condición"
                            icon="accessibility_new"
                            wire:model="familiares.{{ $index }}.condicion"
                            data-offline-field="condicion"
                            :error="$errors->first('familiares.'.$index.'.condicion')"
                        >
                            @foreach ($condiciones as $condicion)
                                <option value="{{ $condicion->value }}">{{ $condicion->label() }}</option>
                            @endforeach
                        </x-m3.select-field>
                        <x-m3.text-field label="Nombre" icon="person" wire:model="familiares.{{ $index }}.nombre" data-offline-field="nombre" />
                        <x-m3.text-field label="Apellido" icon="person" wire:model="familiares.{{ $index }}.apellido" data-offline-field="apellido" />
                        <x-m3.text-field label="Cédula" icon="id_card" wire:model="familiares.{{ $index }}.cedula" data-offline-field="cedula" />
                        <x-m3.text-field label="Teléfono" icon="phone" wire:model="familiares.{{ $index }}.telefono" data-offline-field="telefono" />
                        <x-m3.text-field label="Fecha nacimiento" icon="cake" type="date" wire:model="familiares.{{ $index }}.fecha_nacimiento" data-offline-field="fecha_nacimiento" />
                    </div>
                @empty
                    <p class="text-sm text-m3-on-surface-variant">Opcional: agregue familiares del núcleo hospedado.</p>
                @endforelse
            </section>
        @endif

        {{-- Paso: Foto y confirmar --}}
        @php
            $pasoFoto = $this->requiereRegistroHogar ? 3 : 2;
        @endphp
        @if ($paso === $pasoFoto)
            <section class="m3-card space-y-3">
                <h2 class="flex items-center gap-2 text-base font-semibold text-m3-on-surface">
                    <span class="material-symbols-outlined text-m3-primary">photo_camera</span>
                    Foto testigo de ingreso
                </h2>
                <p class="text-xs text-m3-on-surface-variant">Opcional · evidencia visual del Invitado al momento del registro.</p>

                <div @class([
                    'flex items-start gap-4 rounded-xl border p-4 transition',
                    'border-m3-primary/40 bg-white ring-1 ring-m3-primary/20' => $foto,
                    'border-m3-outline-variant/60 bg-m3-surface-container' => ! $foto,
                ])>
                    <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-m3-surface-container ring-2 ring-m3-primary/30">
                        @if ($foto)
                            <img
                                src="{{ $foto->temporaryUrl() }}"
                                alt="Miniatura foto testigo"
                                class="h-full w-full object-cover"
                                wire:key="foto-preview-{{ $foto->getFilename() }}"
                            >
                        @else
                            <img id="foto-preview-local" alt="Miniatura foto testigo" class="hidden h-full w-full object-cover">
                            <div id="foto-preview-placeholder" class="flex h-full w-full flex-col items-center justify-center gap-1 bg-m3-tertiary-container text-m3-on-tertiary-container">
                                <span class="material-symbols-outlined text-3xl">add_a_photo</span>
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 space-y-2">
                        <label for="foto-captura" class="m3-btn-filled inline-flex !min-h-[40px] w-full cursor-pointer !text-sm">
                            <span class="material-symbols-outlined">photo_camera</span>
                            {{ $foto ? 'Retomar foto' : 'Abrir cámara' }}
                        </label>
                        @if ($foto)
                            <button type="button" wire:click="quitarFoto" class="m3-btn-text !text-m3-secondary !text-xs">
                                Quitar foto
                            </button>
                        @endif
                        <input type="file" id="foto-captura" accept="image/*" capture="environment" class="sr-only">
                        <input type="file" id="foto-input" wire:model="foto" accept="image/*" data-offline-field="foto" class="hidden">
                    </div>
                </div>

                @error('foto') <p class="m3-error !px-0">{{ $message }}</p> @enderror
            </section>

            <section class="m3-card space-y-2 !bg-m3-primary-container/30">
                <h3 class="text-sm font-semibold text-m3-on-surface">Resumen</h3>
                @if ($this->requiereRegistroHogar)
                    <p class="text-sm"><span class="text-m3-on-surface-variant">Hogar:</span> código automático al guardar</p>
                    <p class="text-sm"><span class="text-m3-on-surface-variant">Acogida:</span> {{ collect($tiposAnfitrion)->firstWhere('value', $hogar_tipo_anfitrion)?->label() ?? '—' }}</p>
                @elseif ($refugio)
                    <p class="text-sm"><span class="text-m3-on-surface-variant">Hogar:</span> {{ $refugio->nombre }}</p>
                @endif
                <p class="text-sm"><span class="text-m3-on-surface-variant">Jefe:</span> {{ trim($nombre.' '.$apellido) ?: '—' }}</p>
                <p class="text-sm"><span class="text-m3-on-surface-variant">Familiares:</span> {{ count($familiares) }}</p>
            </section>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($paso > 0)
                <button type="button" wire:click="anterior" class="m3-btn-outlined">
                    Anterior
                </button>
            @else
                <span></span>
            @endif

            @if ($paso < $this->totalPasos - 1)
                <button type="button" wire:click="siguiente" class="m3-btn-filled">
                    Siguiente
                </button>
            @else
                <x-m3.button icon="save" variant="danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="guardar">Registrar núcleo familiar</span>
                    <span wire:loading wire:target="guardar">Guardando…</span>
                </x-m3.button>
            @endif
        </div>
    </form>
</div>

@script
<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
<script>
    const captura = document.getElementById('foto-captura');
    const livewireInput = document.getElementById('foto-input');
    const previewLocal = document.getElementById('foto-preview-local');
    const previewPlaceholder = document.getElementById('foto-preview-placeholder');

    function showLocalPreview(file) {
        if (!previewLocal || !file) return;
        const url = URL.createObjectURL(file);
        previewLocal.onload = () => URL.revokeObjectURL(url);
        previewLocal.src = url;
        previewLocal.classList.remove('hidden');
        previewPlaceholder?.classList.add('hidden');
    }

    if (captura && livewireInput) {
        captura.addEventListener('change', async (event) => {
            const file = event.target.files?.[0];
            if (!file) return;

            showLocalPreview(file);

            try {
                const compressed = await imageCompression(file, { maxSizeMB: 0.8, maxWidthOrHeight: 1280, useWebWorker: true });
                const dt = new DataTransfer();
                dt.items.add(compressed);
                livewireInput.files = dt.files;
                livewireInput.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (e) {
                const dt = new DataTransfer();
                dt.items.add(file);
                livewireInput.files = dt.files;
                livewireInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
</script>
@endscript
