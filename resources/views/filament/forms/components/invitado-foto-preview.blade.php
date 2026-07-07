@php
    /** @var \App\Models\Invitado|null $record */
    $record = $getRecord();
    $invitadoConFoto = $record?->invitadoConFoto();
    $url = $invitadoConFoto?->fotoUrl();
    $esFotoDelJefe = $invitadoConFoto !== null
        && $record !== null
        && $invitadoConFoto->id !== $record->id;
@endphp

@if ($url)
    <div class="space-y-2">
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-block">
            <img
                src="{{ $url }}"
                alt="Foto testigo de {{ $invitadoConFoto->nombreCompleto() }}"
                class="max-h-56 w-auto max-w-full rounded-xl object-cover ring-2 ring-primary-500/30 shadow-sm"
            />
        </a>
        @if ($esFotoDelJefe)
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Foto del jefe de familia: {{ $invitadoConFoto->nombreCompleto() }}
            </p>
        @endif
        <p class="text-xs text-gray-500 dark:text-gray-400">Clic para abrir en tamaño completo</p>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">Sin foto de ingreso registrada.</p>
@endif
