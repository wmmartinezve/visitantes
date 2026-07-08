<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvitadoResource\Pages;

use App\Enums\ActivityAction;
use App\Enums\ActivityChannel;
use App\Models\Invitado;
use App\Services\ActivityLogService;
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

        app(ActivityLogService::class)->log(
            ActivityAction::FotoAttached,
            $target->fresh(),
            'Foto testigo de ingreso (panel admin)',
            [
                'old' => ['foto_ingreso' => $previous],
                'new' => ['foto_ingreso' => $finalPath],
            ],
            channel: ActivityChannel::Admin,
        );

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
