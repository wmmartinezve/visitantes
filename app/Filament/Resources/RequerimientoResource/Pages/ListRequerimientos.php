<?php

namespace App\Filament\Resources\RequerimientoResource\Pages;

use App\Filament\Resources\RequerimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequerimientos extends ListRecords
{
    protected static string $resource = RequerimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
