<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Resumen — {{ config('visitantes.estado') }}">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Refugios</p>
                    <p class="text-2xl font-semibold">{{ $this->resumen['refugios'] }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Centros activos</p>
                    <p class="text-2xl font-semibold">{{ $this->resumen['centros'] }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Invitados activos</p>
                    <p class="text-2xl font-semibold">{{ $this->resumen['invitados'] }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Requerimientos</p>
                    <p class="text-2xl font-semibold">{{ $this->resumen['requerimientos'] }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Exportar datos (CSV)">
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Descarga reportes consolidados del estado {{ config('visitantes.estado') }} para análisis offline o entrega institucional.
            </p>
            <div class="flex flex-wrap gap-3">
                <x-filament::button icon="heroicon-o-user-group" wire:click="exportInvitados">
                    Invitados (jefes de familia)
                </x-filament::button>
                <x-filament::button icon="heroicon-o-clipboard-document-list" color="gray" wire:click="exportRequerimientos">
                    Requerimientos
                </x-filament::button>
                <x-filament::button icon="heroicon-o-archive-box" color="gray" wire:click="exportInventario">
                    Inventario global
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
