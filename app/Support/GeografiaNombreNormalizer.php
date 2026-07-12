<?php

declare(strict_types=1);

namespace App\Support;

final class GeografiaNombreNormalizer
{
    public static function normalize(string $nombre): string
    {
        $normalized = mb_strtolower(trim($nombre), 'UTF-8');
        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
            'ü' => 'u',
        ]);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return $normalized;
    }

    public static function equals(string $a, string $b): bool
    {
        return self::normalize($a) === self::normalize($b);
    }
}
