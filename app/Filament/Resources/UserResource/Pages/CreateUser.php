<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    use LogsFilamentRecordActivity;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::normalizeRoleFields($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data = self::normalizeRoleFields($data);

        $user = new User;
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->forceFill([
            'rol' => $data['rol'],
            'refugio_id' => $data['refugio_id'] ?? null,
            'centro_acopio_id' => $data['centro_acopio_id'] ?? null,
        ]);
        $user->save();

        return $user;
    }

    protected function afterCreate(): void
    {
        $this->logFilamentCreated($this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeRoleFields(array $data): array
    {
        return match ($data['rol'] ?? null) {
            \App\Enums\UserRole::Admin->value => array_merge($data, [
                'refugio_id' => null,
                'centro_acopio_id' => null,
            ]),
            \App\Enums\UserRole::Anfitrion->value => array_merge($data, [
                'centro_acopio_id' => null,
            ]),
            \App\Enums\UserRole::CentroAcopio->value => array_merge($data, [
                'refugio_id' => null,
            ]),
            default => $data,
        };
    }
}
