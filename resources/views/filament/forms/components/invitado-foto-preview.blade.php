@php
    /** @var \App\Models\Invitado|null $record */
    use App\Support\InvitadoFotoStorage;

    $record = $getRecord();
    $dueñoRuta = null;

    if ($record !== null) {
        if (! blank($record->foto_ingreso)) {
            $dueñoRuta = $record;
        } elseif ($record->jefeFamilia !== null && ! blank($record->jefeFamilia->foto_ingreso)) {
            $dueñoRuta = $record->jefeFamilia;
        } elseif ($record->jefe_familia_id !== null) {
            $record->loadMissing('jefeFamilia');
            if ($record->jefeFamilia !== null && ! blank($record->jefeFamilia->foto_ingreso)) {
                $dueñoRuta = $record->jefeFamilia;
            }
        }
    }

    $url = $dueñoRuta !== null
        ? InvitadoFotoStorage::displayUrl($dueñoRuta->foto_ingreso, $dueñoRuta)
        : null;
    $esFotoDelJefe = $dueñoRuta !== null
        && $record !== null
        && $dueñoRuta->id !== $record->id;
@endphp

@if ($url)
    <div class="space-y-2">
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-block">
            <img
                src="{{ $url }}"
                alt="Foto testigo de {{ $dueñoRuta->nombreCompleto() }}"
                class="max-h-56 w-auto max-w-full rounded-xl object-cover ring-2 ring-primary-500/30 shadow-sm"
            />
        </a>
        @if ($esFotoDelJefe)
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Foto del jefe de familia: {{ $dueñoRuta->nombreCompleto() }}
            </p>
        @endif
        <p class="text-xs text-gray-500 dark:text-gray-400">Clic para abrir en tamaño completo</p>
    </div>
@elseif ($dueñoRuta !== null)
    <div class="rounded-lg border border-warning-300 bg-warning-50 p-3 dark:border-warning-600 dark:bg-warning-950/30">
        <p class="text-sm text-warning-800 dark:text-warning-200">
            Hay una ruta de foto registrada, pero el archivo no está en el almacenamiento (S3).
            Use <strong>Reemplazar foto</strong> abajo o vuelva a subirla desde la app móvil.
        </p>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">Sin foto de ingreso registrada.</p>
@endif
