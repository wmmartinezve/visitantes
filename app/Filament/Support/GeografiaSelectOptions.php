<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Filament\Forms\Get;

final class GeografiaSelectOptions
{
    /** @return array<int|string, string> */
    public static function estados(): array
    {
        return Estado::query()->orderBy('nombre')->pluck('nombre', 'id')->all();
    }

    /**
     * Municipios del estado elegido (sin inferir estado desde otros campos).
     *
     * @return array<int|string, string>
     */
    public static function municipios(Get $get, string $estadoField): array
    {
        $estadoId = $get($estadoField);

        if (! filled($estadoId)) {
            return [];
        }

        return Municipio::query()
            ->where('estado_id', $estadoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }

    /**
     * Parroquias del municipio elegido.
     *
     * @return array<int|string, string>
     */
    public static function parroquias(Get $get, string $municipioField): array
    {
        $municipioId = $get($municipioField);

        if (! filled($municipioId)) {
            return [];
        }

        return Parroquia::query()
            ->where('municipio_id', $municipioId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }
}
