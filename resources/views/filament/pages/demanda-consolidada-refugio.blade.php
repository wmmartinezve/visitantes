<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Agrupa los requerimientos de Invitados por refugio e ítem para planificar envíos con mayor volumen.
            Selecciona una fila para ver centros de acopio con stock suficiente y asignar el lote completo.
        </p>

        {{ $this->form }}

        <x-filament::section heading="Demanda agrupada">
            @if($demanda->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No hay requerimientos con los filtros seleccionados.
                </p>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Refugio</th>
                                <th class="px-4 py-3 text-left font-medium">Ítem</th>
                                <th class="px-4 py-3 text-right font-medium">Total u.</th>
                                <th class="px-4 py-3 text-right font-medium">Req.</th>
                                <th class="px-4 py-3 text-right font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @php $refugioAnterior = null; @endphp
                            @foreach($demanda as $fila)
                                @php $key = $this->grupoKey($fila); @endphp
                                <tr
                                    wire:key="demanda-{{ $key }}"
                                    @class([
                                        'bg-primary-50/40 dark:bg-primary-950/20' => $grupoSeleccionado === $key,
                                    ])
                                >
                                    <td class="px-4 py-3 align-top">
                                        @if($refugioAnterior !== $fila['refugio_nombre'])
                                            <p class="font-semibold">{{ $fila['refugio_nombre'] }}</p>
                                            <p class="text-xs text-gray-500">{{ $fila['parroquia_nombre'] ?? '—' }}</p>
                                            @php $refugioAnterior = $fila['refugio_nombre']; @endphp
                                        @else
                                            <span class="text-gray-400">↳</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ $fila['item_solicitado'] }}</p>
                                        @if($fila['categoria'])
                                            <p class="text-xs text-gray-500">{{ $fila['categoria'] }} · {{ $fila['subcategoria'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ $fila['cantidad_total'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ $fila['requerimientos_count'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-filament::button
                                            size="sm"
                                            color="{{ $grupoSeleccionado === $key ? 'primary' : 'gray' }}"
                                            wire:click="seleccionarGrupo('{{ $key }}')"
                                        >
                                            {{ $grupoSeleccionado === $key ? 'Seleccionado' : 'Planificar' }}
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        @if($grupoActivo !== null)
            <x-filament::section
                heading="Envío consolidado: {{ $grupoActivo['item_solicitado'] }} → {{ $grupoActivo['refugio_nombre'] }}"
            >
                <div class="mb-4 space-y-1">
                    <p class="text-sm">
                        <span class="font-medium">Volumen total:</span>
                        {{ $grupoActivo['cantidad_total'] }} unidades en
                        {{ $grupoActivo['requerimientos_count'] }} requerimiento(s).
                    </p>
                    <details class="text-sm text-gray-600 dark:text-gray-400">
                        <summary class="cursor-pointer font-medium">Ver desglose por Invitado</summary>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach($grupoActivo['invitados'] as $invitado)
                                <li>{{ $invitado }}</li>
                            @endforeach
                        </ul>
                    </details>
                </div>

                @if(($filtros['estatus'] ?? 'pendiente') !== 'pendiente')
                    <p class="text-sm text-warning-600 dark:text-warning-400">
                        La asignación en lote solo está disponible para requerimientos pendientes.
                    </p>
                @elseif($centrosDisponibles->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Ningún centro de acopio tiene stock suficiente para este envío consolidado.
                    </p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Centro</th>
                                    <th class="px-4 py-3 text-right font-medium">Stock</th>
                                    <th class="px-4 py-3 text-right font-medium">Distancia</th>
                                    <th class="px-4 py-3 text-right font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($centrosDisponibles as $row)
                                    <tr wire:key="lote-centro-{{ $row['centro']->id }}">
                                        <td class="px-4 py-3">
                                            <p class="font-medium">{{ $row['centro']->nombre }}</p>
                                            <p class="text-xs text-gray-500">{{ $row['centro']->parroquia?->nombre }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-right">{{ $row['cantidad'] }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if($row['distancia_km'] !== null)
                                                {{ $row['distancia_km'] }} km
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <x-filament::button
                                                size="sm"
                                                wire:click="asignarLote({{ $row['centro']->id }})"
                                                wire:loading.attr="disabled"
                                            >
                                                Asignar lote
                                            </x-filament::button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Ordenado por proximidad al refugio y mayor stock. El operador de acopio verá cada requerimiento asignado en su app.
                    </p>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
