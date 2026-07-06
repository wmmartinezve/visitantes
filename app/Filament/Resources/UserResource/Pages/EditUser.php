<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CreateUser::normalizeRoleFields($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $data = CreateUser::normalizeRoleFields($data);

        $record->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (filled($data['password'] ?? null)) {
            $record->password = $data['password'];
        }

        $record->forceFill([
            'rol' => $data['rol'],
            'refugio_id' => $data['refugio_id'] ?? null,
            'centro_acopio_id' => $data['centro_acopio_id'] ?? null,
        ]);
        $record->save();

        return $record;
    }
}
