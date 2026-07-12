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
     * @return array<int|string, string>
     */
    public static function municipios(Get $get, string $estadoField, string $municipioField): array
    {
        $estadoId = self::resolveEstadoId($get, $estadoField, $municipioField);

        if ($estadoId === null) {
            return self::fallbackMunicipio($get($municipioField));
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
    public static function parroquias(Get $get, string $municipioField, string $parroquiaField): array
    {
        $municipioId = self::resolveMunicipioId($get, $municipioField, $parroquiaField);

        if ($municipioId === null) {
            return self::fallbackParroquia($get($parroquiaField));
        }

        return Parroquia::query()
            ->where('municipio_id', $municipioId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }

    public static function resolveEstadoId(Get $get, string $estadoField, string $municipioField): ?int
    {
        if (filled($get($estadoField))) {
            return (int) $get($estadoField);
        }

        $municipioId = $get($municipioField);

        if (! filled($municipioId)) {
            return null;
        }

        $estadoId = Municipio::query()->whereKey($municipioId)->value('estado_id');

        return $estadoId !== null ? (int) $estadoId : null;
    }

    public static function resolveMunicipioId(Get $get, string $municipioField, string $parroquiaField): ?int
    {
        if (filled($get($municipioField))) {
            return (int) $get($municipioField);
        }

        $parroquiaId = $get($parroquiaField);

        if (! filled($parroquiaId)) {
            return null;
        }

        $municipioId = Parroquia::query()->whereKey($parroquiaId)->value('municipio_id');

        return $municipioId !== null ? (int) $municipioId : null;
    }

    /** @return array<int|string, string> */
    private static function fallbackMunicipio(mixed $municipioId): array
    {
        if (! filled($municipioId)) {
            return [];
        }

        $municipio = Municipio::query()->find($municipioId);

        return $municipio !== null ? [$municipio->id => $municipio->nombre] : [];
    }

    /** @return array<int|string, string> */
    private static function fallbackParroquia(mixed $parroquiaId): array
    {
        if (! filled($parroquiaId)) {
            return [];
        }

        $parroquia = Parroquia::query()->find($parroquiaId);

        return $parroquia !== null ? [$parroquia->id => $parroquia->nombre] : [];
    }
}
