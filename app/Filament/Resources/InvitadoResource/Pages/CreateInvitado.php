<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvitadoResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\InvitadoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitado extends CreateRecord
{
    use HandlesInvitadoFotoUpload;
    use LogsFilamentRecordActivity;

    protected static string $resource = InvitadoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['es_jefe_familia'] ?? true) {
            $data['jefe_familia_id'] = null;
        }

        unset($data['es_jefe_familia'], $data['foto_reemplazo']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logFilamentCreated($this->getRecord());
        $this->persistFotoReemplazo($this->getRecord());
    }
}
