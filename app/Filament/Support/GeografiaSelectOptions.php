<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Filament\Forms\Get;

final class GeografiaSelectOptions
{
    /** @var array<int|string, string>|null */
    private static ?array $estadosCache = null;

    /** @return array<int|string, string> */
    public static function estados(): array
    {
        if (self::$estadosCache !== null) {
            return self::$estadosCache;
        }

        self::$estadosCache = Estado::query()
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->mapWithKeys(fn (string $nombre, int|string $id): array => [(string) $id => $nombre])
            ->all();

        return self::$estadosCache;
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
            ->mapWithKeys(fn (string $nombre, int|string $id): array => [(string) $id => $nombre])
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
            ->mapWithKeys(fn (string $nombre, int|string $id): array => [(string) $id => $nombre])
            ->all();
    }
}
