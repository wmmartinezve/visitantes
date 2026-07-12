<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvitadoResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\InvitadoResource;
use App\Support\NucleoFamiliarPorHogar;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateInvitado extends CreateRecord
{
    use HandlesInvitadoFotoUpload;
    use LogsFilamentRecordActivity;

    protected static string $resource = InvitadoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['es_jefe_familia'] ?? true) {
            $hogarId = (int) ($data['hogar_solidario_id'] ?? 0);

            if ($hogarId > 0) {
                NucleoFamiliarPorHogar::assertPuedeRegistrarJefe($hogarId);
            }

            $data['jefe_familia_id'] = null;
        }

        unset($data['es_jefe_familia'], $data['foto_reemplazo']);

        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        if ($data['es_jefe_familia'] ?? true) {
            return;
        }

        $hogarId = (int) ($data['hogar_solidario_id'] ?? 0);
        $jefeId = $data['jefe_familia_id'] ?? null;

        if ($hogarId <= 0 || $jefeId === null) {
            throw ValidationException::withMessages([
                'jefe_familia_id' => 'Seleccione el jefe de familia del núcleo en este hogar solidario.',
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $this->logFilamentCreated($this->getRecord());
        $this->persistFotoReemplazo($this->getRecord());
    }
}
