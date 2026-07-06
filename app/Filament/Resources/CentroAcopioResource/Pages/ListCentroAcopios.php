<?php

namespace App\Filament\Resources\CentroAcopioResource\Pages;

use App\Filament\Resources\CentroAcopioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCentroAcopios extends ListRecords
{
    protected static string $resource = CentroAcopioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
