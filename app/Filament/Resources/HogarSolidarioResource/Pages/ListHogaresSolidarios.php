<?php

declare(strict_types=1);

namespace App\Filament\Resources\HogarSolidarioResource\Pages;

use App\Filament\Resources\HogarSolidarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHogaresSolidarios extends ListRecords
{
    protected static string $resource = HogarSolidarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
