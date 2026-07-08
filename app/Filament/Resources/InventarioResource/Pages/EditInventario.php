<?php

declare(strict_types=1);

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventario extends EditRecord
{
    use LogsFilamentRecordActivity;

    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(fn () => $this->logFilamentDeleted($this->getRecord())),
        ];
    }

    protected function beforeSave(): void
    {
        $this->captureActivityBeforeSave($this->getRecord());
    }

    protected function afterSave(): void
    {
        $this->logFilamentUpdated($this->getRecord());
    }
}
