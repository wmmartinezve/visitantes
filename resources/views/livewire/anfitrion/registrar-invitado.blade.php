<div class="space-y-4">
    <x-m3.banner type="info" class="flex items-center gap-2">
        <span class="material-symbols-outlined">location_on</span>
        <div>
            <p class="text-xs opacity-80">Refugio asignado</p>
            <p class="font-medium">{{ $refugio?->nombre ?? '—' }}</p>
        </div>
    </x-m3.banner>

    <form wire:submit="guardar" data-offline-form data-offline-type="invitado.registro" class="space-y-4">
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
                        <span class="absolute bottom-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-m3-success text-white shadow-sm">
                            <span class="material-symbols-outlined text-sm">check</span>
                        </span>
                    @else
                        <img id="foto-preview-local" alt="Miniatura foto testigo" class="hidden h-full w-full object-cover">
                        <div id="foto-preview-placeholder" class="flex h-full w-full flex-col items-center justify-center gap-1 bg-m3-tertiary-container text-m3-on-tertiary-container">
                            <span class="material-symbols-outlined text-3xl">add_a_photo</span>
                        </div>
                    @endif
                </div>

                <div class="min-w-0 flex-1 space-y-2">
                    @if ($foto)
                        <span class="inline-flex rounded-md bg-m3-primary-container px-2 py-0.5 text-xs font-semibold text-m3-primary">Testigo capturado</span>
                        <p class="text-sm font-medium text-m3-on-surface">Foto lista para guardar</p>
                        <p id="foto-preview-name" class="truncate text-xs text-m3-on-surface-variant"></p>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <label for="foto-captura" class="m3-btn-tonal !min-h-[36px] cursor-pointer !px-3 !py-1.5 !text-xs">
                                <span class="material-symbols-outlined text-base">cameraswitch</span>
                                Retomar
                            </label>
                            <button type="button" wire:click="quitarFoto" class="m3-btn-text !min-h-[36px] !px-2 !text-m3-secondary !text-xs">
                                <span class="material-symbols-outlined text-base">delete</span>
                                Quitar
                            </button>
                        </div>
                    @else
                        <p class="text-sm font-medium text-m3-on-surface">Tomar foto testigo</p>
                        <p class="text-xs text-m3-on-surface-variant">Use la cámara del dispositivo. La miniatura aparecerá aquí al capturar.</p>
                        <label for="foto-captura" class="m3-btn-filled mt-1 inline-flex !min-h-[40px] w-full cursor-pointer !text-sm">
                            <span class="material-symbols-outlined">photo_camera</span>
                            Abrir cámara
                        </label>
                    @endif

                    <input type="file" id="foto-captura" accept="image/*" capture="environment" class="sr-only">
                    <input type="file" id="foto-input" wire:model="foto" accept="image/*" data-offline-field="foto" class="hidden">
                </div>
            </div>

            @error('foto') <p class="m3-error !px-0">{{ $message }}</p> @enderror
            <div wire:loading wire:target="foto" class="flex items-center gap-2 text-xs text-m3-on-surface-variant">
                <span class="material-symbols-outlined animate-spin text-base">progress_activity</span>
                Procesando imagen…
            </div>
        </section>

        <section class="m3-card space-y-4">
            <h2 class="flex items-center gap-2 text-base font-medium text-m3-on-surface">
                <span class="material-symbols-outlined text-m3-primary">badge</span>
                Datos del Invitado
            </h2>

            <x-m3.text-field label="Nombre" icon="person" wire:model="nombre" data-offline-field="nombre" :error="$errors->first('nombre')" />
            <x-m3.text-field label="Apellido" icon="person" wire:model="apellido" data-offline-field="apellido" :error="$errors->first('apellido')" />
            <x-m3.text-field label="Cédula" icon="id_card" wire:model="cedula" data-offline-field="cedula" />
            <x-m3.text-field label="Teléfono" icon="phone" type="tel" wire:model="telefono" data-offline-field="telefono" />
            <x-m3.text-field label="Fecha de nacimiento" icon="cake" type="date" wire:model="fecha_nacimiento" data-offline-field="fecha_nacimiento" :error="$errors->first('fecha_nacimiento')" />
        </section>

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
                    <x-m3.text-field label="Nombre" icon="person" wire:model="familiares.{{ $index }}.nombre" data-offline-field="nombre" />
                    <x-m3.text-field label="Apellido" icon="person" wire:model="familiares.{{ $index }}.apellido" data-offline-field="apellido" />
                    <x-m3.text-field label="Cédula" icon="id_card" wire:model="familiares.{{ $index }}.cedula" data-offline-field="cedula" />
                    <x-m3.text-field label="Teléfono" icon="phone" wire:model="familiares.{{ $index }}.telefono" data-offline-field="telefono" />
                    <x-m3.text-field label="Fecha nacimiento" icon="cake" type="date" wire:model="familiares.{{ $index }}.fecha_nacimiento" data-offline-field="fecha_nacimiento" />
                </div>
            @empty
                <p class="text-sm text-m3-on-surface-variant">Opcional: agrega familiares del jefe de familia.</p>
            @endforelse
        </section>

        <x-m3.button icon="save" variant="danger" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="guardar">Guardar Invitado</span>
            <span wire:loading wire:target="guardar">Guardando…</span>
        </x-m3.button>
    </form>
</div>

@script
<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
<script>
    const captura = document.getElementById('foto-captura');
    const livewireInput = document.getElementById('foto-input');
    const previewLocal = document.getElementById('foto-preview-local');
    const previewPlaceholder = document.getElementById('foto-preview-placeholder');
    const previewName = document.getElementById('foto-preview-name');

    function showLocalPreview(file) {
        if (!previewLocal || !file) return;
        const url = URL.createObjectURL(file);
        previewLocal.onload = () => URL.revokeObjectURL(url);
        previewLocal.src = url;
        previewLocal.classList.remove('hidden');
        previewPlaceholder?.classList.add('hidden');
        if (previewName) previewName.textContent = file.name;
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
