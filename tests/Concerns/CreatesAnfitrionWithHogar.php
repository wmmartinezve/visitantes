<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\HogarSolidario;
use App\Models\Parroquia;
use App\Models\User;

trait CreatesAnfitrionWithHogar
{
    /**
     * @param  array<string, mixed>  $hogarAttributes
     * @param  array<string, mixed>  $userAttributes
     * @return array{0: User, 1: HogarSolidario}
     */
    protected function createAnfitrionWithHogar(array $hogarAttributes = [], array $userAttributes = []): array
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $hogar = HogarSolidario::query()->create(array_merge([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ], $hogarAttributes));

        $anfitrion = User::factory()->create(array_merge([
            'rol' => UserRole::Anfitrion,
        ], $userAttributes));

        $hogar->forceFill(['anfitrion_user_id' => $anfitrion->id])->save();
        $anfitrion->forceFill(['hogar_solidario_id' => $hogar->id])->save();

        return [$anfitrion->fresh(), $hogar->fresh()];
    }
}
