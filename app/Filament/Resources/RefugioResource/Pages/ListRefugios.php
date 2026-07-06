<?php

namespace App\Filament\Resources\RefugioResource\Pages;

use App\Filament\Resources\RefugioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRefugios extends ListRecords
{
    protected static string $resource = RefugioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
