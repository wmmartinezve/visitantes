<?php

namespace App\Filament\Resources\CentroAcopioResource\Pages;

use App\Filament\Resources\CentroAcopioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCentroAcopio extends EditRecord
{
    protected static string $resource = CentroAcopioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
