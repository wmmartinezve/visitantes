<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComunaResource\Pages;

use App\Filament\Resources\ComunaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComuna extends EditRecord
{
    protected static string $resource = ComunaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
