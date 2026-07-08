<?php

declare(strict_types=1);

namespace App\Filament\Resources\RequerimientoResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\RequerimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequerimiento extends EditRecord
{
    use LogsFilamentRecordActivity;

    protected static string $resource = RequerimientoResource::class;

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
