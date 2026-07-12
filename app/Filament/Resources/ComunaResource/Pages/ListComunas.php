<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComunaResource\Pages;

use App\Filament\Resources\ComunaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComunas extends ListRecords
{
    protected static string $resource = ComunaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
