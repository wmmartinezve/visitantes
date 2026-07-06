<div class="space-y-4">
    @if (session('success'))
        <x-m3.banner type="success">{{ session('success') }}</x-m3.banner>
    @endif

    <a href="{{ route('anfitrion.invitados') }}" class="m3-btn-text !justify-start !px-0">
        <span class="material-symbols-outlined">arrow_back</span>
        Volver
    </a>

    <div class="m3-guest-card !pointer-events-none">
        <div class="m3-guest-card-accent" aria-hidden="true">
            <span class="ve-yellow"></span>
            <span class="ve-blue"></span>
            <span class="ve-red"></span>
        </div>
        <div class="flex gap-4 p-4">
            @if ($fotoUrl = $invitado->fotoUrl())
                <img src="{{ $fotoUrl }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-m3-primary/20">
            @else
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-m3-primary-container text-xl font-bold text-m3-primary">
                    {{ strtoupper(substr($invitado->nombre, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="text-xl font-semibold text-m3-on-surface">{{ $invitado->nombreCompleto() }}</h1>
                <p class="mt-1 flex items-center gap-1 text-sm text-m3-on-surface-variant">
                    <span class="material-symbols-outlined text-base">id_card</span>{{ $invitado->cedula ?: 'Sin cédula' }}
                </p>
                <p class="flex items-center gap-1 text-sm text-m3-on-surface-variant">
                    <span class="material-symbols-outlined text-base">phone</span>{{ $invitado->telefono ?: 'Sin teléfono' }}
                </p>
            </div>
        </div>
    </div>

    @if ($invitado->miembrosFamilia->isNotEmpty())
        <x-m3.section-header :title="'Núcleo familiar ('.$invitado->miembrosFamilia->count().')'" />
        <div class="space-y-2">
            @foreach ($invitado->miembrosFamilia as $familiar)
                <div class="m3-card !flex items-center gap-3 !p-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-m3-tertiary-container text-sm font-semibold text-m3-on-tertiary-container">
                        {{ strtoupper(substr($familiar->nombre, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-medium">{{ $familiar->nombreCompleto() }}</p>
                        <p class="text-xs text-m3-on-surface-variant">{{ $familiar->parentesco ?? 'Sin parentesco' }}@if($familiar->cedula) · {{ $familiar->cedula }}@endif</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-m3.section-header title="Requerimientos" />

    <div class="m3-card space-y-3">
        <form wire:submit="agregarRequerimiento" data-offline-form data-offline-type="requerimiento.create" data-offline-invitado-id="{{ $invitado->id }}" class="space-y-3">
            <x-m3.select-field label="Categoría" icon="category"
                wire:model.live="categoria" data-offline-field="categoria" :error="$errors->first('categoria')">
                <option value="">Seleccione…</option>
                @foreach(array_keys($insumosCatalogo) as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </x-m3.select-field>

            <x-m3.select-field label="Subcategoría" icon="shopping_bag"
                wire:model="subcategoria" data-offline-field="subcategoria" :error="$errors->first('subcategoria')"
                :disabled="$categoria === ''">
                <option value="">Seleccione…</option>
                @foreach($subcategoriasDisponibles as $sub)
                    <option value="{{ $sub }}">{{ $sub }}</option>
                @endforeach
            </x-m3.select-field>

            <div class="flex gap-2">
                <x-m3.text-field label="Cant." icon="numbers" type="number" wire:model="cantidad" data-offline-field="cantidad" min="1" class="!w-28 flex-shrink-0" />
                <x-m3.button icon="add" variant="danger" class="flex-1">Agregar</x-m3.button>
            </div>
        </form>

        <div class="divide-y divide-m3-outline-variant/30">
            @forelse ($invitado->requerimientos as $req)
                <div class="flex items-center justify-between py-3">
                    <div>
                        <p class="font-medium">{{ $req->subcategoria ?? $req->item_solicitado }}</p>
                        <p class="text-xs text-m3-on-surface-variant">
                            {{ $req->categoria ?? '—' }} · Cantidad: {{ $req->cantidad }}
                        </p>
                    </div>
                    <x-m3.status-chip :estatus="$req->estatus?->value" :label="$req->estatus?->label()" />
                </div>
            @empty
                <p class="py-6 text-center text-sm text-m3-on-surface-variant">Sin requerimientos.</p>
            @endforelse
        </div>
    </div>
</div>
