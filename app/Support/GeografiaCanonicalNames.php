<?php

declare(strict_types=1);

namespace App\Support;

final class GeografiaCanonicalNames
{
    /** @var array<string, list<string>>|null */
    private static ?array $anzoategui = null;

    public static function parroquiaAnzoategui(string $municipioNombre, string $parroquiaNombre): string
    {
        $map = self::anzoateguiMap();

        if (! isset($map[$municipioNombre])) {
            return $parroquiaNombre;
        }

        foreach ($map[$municipioNombre] as $canonical) {
            if (GeografiaNombreNormalizer::equals($canonical, $parroquiaNombre)) {
                return $canonical;
            }
        }

        return $parroquiaNombre;
    }

    public static function preferParroquiaNombre(string $a, string $b, ?string $canonical = null): string
    {
        if ($canonical !== null) {
            if ($a === $canonical) {
                return $a;
            }

            if ($b === $canonical) {
                return $b;
            }
        }

        return self::qualityScore($a) >= self::qualityScore($b) ? $a : $b;
    }

    private static function qualityScore(string $nombre): int
    {
        $score = 0;

        if (preg_match('/[áéíóúñÁÉÍÓÚÑ]/u', $nombre) === 1) {
            $score += 10;
        }

        if ($nombre === mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8')) {
            $score += 5;
        }

        return $score;
    }

    /** @return array<string, list<string>> */
    private static function anzoateguiMap(): array
    {
        if (self::$anzoategui !== null) {
            return self::$anzoategui;
        }

        /** @var array<string, list<string>> $data */
        $data = require database_path('seeders/data/anzoategui_geografia.php');

        return self::$anzoategui = $data;
    }
}
