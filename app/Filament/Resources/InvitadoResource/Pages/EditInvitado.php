<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvitadoResource\Pages;

use App\Filament\Resources\InvitadoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvitado extends EditRecord
{
    protected static string $resource = InvitadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['es_jefe_familia'] ?? false) {
            $data['jefe_familia_id'] = null;
        }

        unset($data['es_jefe_familia']);

        return $data;
    }
}
