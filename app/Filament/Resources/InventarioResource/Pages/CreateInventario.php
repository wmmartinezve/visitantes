<?php

declare(strict_types=1);

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventario extends CreateRecord
{
    use LogsFilamentRecordActivity;

    protected static string $resource = InventarioResource::class;

    protected function afterCreate(): void
    {
        $this->logFilamentCreated($this->getRecord());
    }
}
