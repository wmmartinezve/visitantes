<div class="space-y-4">
    <x-m3.section-header title="Inventario">
        <x-slot:action>
            <span class="text-xs text-m3-on-surface-variant">Insumos del centro</span>
        </x-slot:action>
    </x-m3.section-header>

    @if(session('status'))
        <x-m3.banner type="success">{{ session('status') }}</x-m3.banner>
    @endif

    <div class="m3-card space-y-4 !p-5">
        <div class="flex items-center gap-2 text-sm font-semibold text-m3-on-surface">
            <span class="material-symbols-outlined text-m3-primary">add_box</span>
            Agregar ítem
        </div>
        <form wire:submit="guardar" data-offline-form data-offline-type="inventario.create" class="space-y-4">
            <x-m3.select-field label="Categoría" icon="category"
                wire:model.live="categoria" data-offline-field="categoria" :error="$errors->first('categoria')">
                <option value="">Seleccione…</option>
                @foreach(array_keys($insumosCatalogo) as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </x-m3.select-field>

            <x-m3.select-field label="Subcategoría" icon="inventory_2"
                wire:model="subcategoria" data-offline-field="subcategoria" :error="$errors->first('subcategoria')"
                :disabled="$categoria === ''">
                <option value="">Seleccione…</option>
                @foreach($subcategoriasDisponibles as $sub)
                    <option value="{{ $sub }}">{{ $sub }}</option>
                @endforeach
            </x-m3.select-field>

            <div class="grid grid-cols-2 gap-3">
                <x-m3.text-field label="Cantidad" icon="numbers" type="number" min="0"
                    wire:model="cantidad" data-offline-field="cantidad" :error="$errors->first('cantidad')" />
                <x-m3.text-field label="Unidad" icon="straighten"
                    wire:model="unidad_medida" data-offline-field="unidad_medida" :error="$errors->first('unidad_medida')" list="unidades-medida" />
                <datalist id="unidades-medida">
                    @foreach(config('visitantes.unidades_medida') as $unidad)
                        <option value="{{ $unidad }}"></option>
                    @endforeach
                </datalist>
            </div>

            <x-m3.button icon="add" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="guardar">Agregar al inventario</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </x-m3.button>
        </form>
    </div>

    <x-m3.section-header :title="'Stock actual ('.$items->count().')'" />

    <div class="space-y-2">
        @forelse($items as $item)
            <div class="m3-card !p-4" wire:key="inv-{{ $item->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <div @class([
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                            'bg-m3-secondary-container text-m3-secondary' => $item->cantidad <= 5,
                            'bg-m3-primary-container text-m3-primary' => $item->cantidad > 5,
                        ])>
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-m3-on-surface">{{ $item->subcategoria ?? $item->item_nombre }}</p>
                            <p class="text-xs text-m3-on-surface-variant">
                                {{ $item->categoria ?? '—' }} · {{ $item->unidad_medida }}
                            </p>
                        </div>
                    </div>

                    @if($editandoId === $item->id)
                        <form wire:submit="actualizarCantidad" data-offline-form data-offline-type="inventario.update_cantidad" data-offline-inventario-id="{{ $item->id }}" class="flex items-center gap-2">
                            <input type="number" min="0" wire:model="editCantidad" data-offline-field="cantidad"
                                class="m3-input no-icon !h-10 !w-20 !rounded-md !border-2 !pt-2 !text-center" />
                            <button type="submit" class="m3-btn-text text-m3-primary">
                                <span class="material-symbols-outlined">check</span>
                            </button>
                            <button type="button" wire:click="cancelarEdicion" class="m3-btn-text text-m3-on-surface-variant">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </form>
                    @else
                        <div class="flex items-center gap-2">
                            <span @class([
                                'm3-inventory-qty',
                                'low' => $item->cantidad <= 5,
                                'ok' => $item->cantidad > 5,
                            ])>
                                {{ $item->cantidad }}
                            </span>
                            <button type="button" wire:click="iniciarEdicion({{ $item->id }})"
                                class="m3-btn-text text-m3-primary" title="Editar cantidad">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </button>
                            <button type="button" wire:click="eliminar({{ $item->id }})"
                                wire:confirm="¿Eliminar este ítem del inventario?"
                                class="m3-btn-text text-m3-secondary" title="Eliminar">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <x-m3.empty-state
                icon="inventory_2"
                title="No hay ítems registrados"
                message="Agregue insumos manualmente o conecte a internet para sincronizar."
            />
        @endforelse
    </div>
</div>
