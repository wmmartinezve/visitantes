<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Comuna;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Filament\Forms\Get;

final class GeografiaSelectOptions
{
    /** @var array<string, array<int|string, string>> */
    private static array $estadosCache = [];

    /** @return array<int|string, string> */
    public static function estados(?string $scope = 'all'): array
    {
        $cacheKey = $scope ?? 'all';

        if (isset(self::$estadosCache[$cacheKey])) {
            return self::$estadosCache[$cacheKey];
        }

        $query = Estado::query()->orderBy('nombre');

        if ($scope === 'anzoategui') {
            $query->where('nombre', 'Anzoátegui');
        }

        self::$estadosCache[$cacheKey] = $query
            ->pluck('nombre', 'id')
            ->all();

        return self::$estadosCache[$cacheKey];
    }

    /**
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

    /**
     * @return array<int|string, string>
     */
    public static function comunas(Get $get, string $parroquiaField): array
    {
        $parroquiaId = $get($parroquiaField);

        if (! filled($parroquiaId)) {
            return [];
        }

        return Comuna::query()
            ->where('parroquia_id', $parroquiaId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }
}
