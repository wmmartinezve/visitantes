<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::normalizeRoleFields($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeRoleFields(array $data): array
    {
        return match ($data['rol'] ?? null) {
            UserRole::Admin->value => array_merge($data, [
                'refugio_id' => null,
                'centro_acopio_id' => null,
            ]),
            UserRole::Anfitrion->value => array_merge($data, [
                'centro_acopio_id' => null,
            ]),
            UserRole::CentroAcopio->value => array_merge($data, [
                'refugio_id' => null,
            ]),
            default => $data,
        };
    }
}
