<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvitadoResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\InvitadoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvitado extends EditRecord
{
    use HandlesInvitadoFotoUpload;
    use LogsFilamentRecordActivity;

    protected static string $resource = InvitadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(fn () => $this->logFilamentDeleted($this->getRecord())),
            Actions\ForceDeleteAction::make()
                ->after(fn () => $this->logFilamentDeleted($this->getRecord(), force: true)),
            Actions\RestoreAction::make()
                ->after(fn () => $this->logFilamentRestored($this->getRecord())),
        ];
    }

    protected function beforeSave(): void
    {
        $this->captureActivityBeforeSave($this->getRecord());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['es_jefe_familia'] ?? false) {
            $data['jefe_familia_id'] = null;
        }

        unset($data['es_jefe_familia'], $data['foto_reemplazo']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->logFilamentUpdated($this->getRecord());
        $this->persistFotoReemplazo($this->getRecord());
        $this->record->unsetRelation('jefeFamilia');
        $this->record->load('jefeFamilia');
    }
}
