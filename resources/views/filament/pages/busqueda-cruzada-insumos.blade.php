<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if(($data['requerimiento_id'] ?? null) !== null)
            <x-filament::section heading="Centros con stock disponible">
                @if($resultados->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron centros de acopio con stock para este ítem.
                    </p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Centro</th>
                                    <th class="px-4 py-3 text-left font-medium">Ítem en stock</th>
                                    <th class="px-4 py-3 text-right font-medium">Disponible</th>
                                    <th class="px-4 py-3 text-right font-medium">Distancia</th>
                                    <th class="px-4 py-3 text-right font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($resultados as $row)
                                    <tr wire:key="centro-{{ $row['centro']->id }}">
                                        <td class="px-4 py-3">
                                            <p class="font-medium">{{ $row['centro']->nombre }}</p>
                                            <p class="text-xs text-gray-500">{{ $row['centro']->parroquia?->nombre }}</p>
                                        </td>
                                        <td class="px-4 py-3">{{ $row['inventario']->item_nombre }}</td>
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
                                                wire:click="asignar({{ $row['centro']->id }})"
                                                wire:loading.attr="disabled"
                                            >
                                                Asignar
                                            </x-filament::button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Ordenado por proximidad al refugio del Invitado y mayor stock disponible.
                    </p>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
