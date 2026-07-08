<?php

declare(strict_types=1);

namespace App\Filament\Resources\RequerimientoResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\RequerimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRequerimiento extends CreateRecord
{
    use LogsFilamentRecordActivity;

    protected static string $resource = RequerimientoResource::class;

    protected function afterCreate(): void
    {
        $this->logFilamentCreated($this->getRecord());
    }
}
