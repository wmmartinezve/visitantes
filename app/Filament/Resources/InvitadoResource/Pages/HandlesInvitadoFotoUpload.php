<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvitadoResource\Pages;

use App\Models\Invitado;
use App\Support\InvitadoFotoStorage;
use Illuminate\Support\Facades\Storage;

trait HandlesInvitadoFotoUpload
{
    protected function persistFotoReemplazo(Invitado $record): void
    {
        $uploaded = $this->form->getState()['foto_reemplazo'] ?? null;

        if (blank($uploaded)) {
            return;
        }

        $path = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;

        if (! is_string($path) || $path === '') {
            return;
        }

        $target = $this->resolveInvitadoFotoTarget($record);

        if ($target === null) {
            return;
        }

        $finalPath = InvitadoFotoStorage::finalizeUploadedPath($path, $target->id);
        $previous = $target->foto_ingreso;

        $target->update(['foto_ingreso' => $finalPath]);

        if ($previous !== null && $previous !== $finalPath) {
            $disk = InvitadoFotoStorage::diskForPath($previous);

            if ($disk !== null) {
                Storage::disk($disk)->delete($previous);
            }
        }
    }

    protected function resolveInvitadoFotoTarget(Invitado $record): ?Invitado
    {
        if ($record->esJefeDeFamilia()) {
            return $record;
        }

        $record->loadMissing('jefeFamilia');

        return $record->jefeFamilia;
    }
}
