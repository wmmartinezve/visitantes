<?php

declare(strict_types=1);

namespace App\Filament\Resources\HogarSolidarioResource\Pages;

use App\Filament\Resources\HogarSolidarioResource;
use App\Filament\Support\HogarSolidarioFichaPdfAction;
use App\Models\HogarSolidario;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHogarSolidario extends EditRecord
{
    protected static string $resource = HogarSolidarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            HogarSolidarioFichaPdfAction::makeHeaderAction(fn (): HogarSolidario => $this->getRecord()),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var HogarSolidario $record */
        $record = $this->getRecord();
        $record->loadMissing('parroquia.municipio.estado');

        if ($record->parroquia !== null) {
            $data['parroquia_id'] = $record->parroquia_id;
            $data['municipio_id'] = $record->parroquia->municipio_id;
            $data['estado_id'] = $record->parroquia->municipio->estado_id;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['estado_id'], $data['municipio_id']);

        return $data;
    }
}
