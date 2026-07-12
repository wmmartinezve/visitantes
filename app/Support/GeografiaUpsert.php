<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Municipio;
use App\Models\Parroquia;

final class GeografiaUpsert
{
    public static function upsertParroquia(Municipio $municipio, string $nombre): Parroquia
    {
        $canonical = GeografiaCanonicalNames::parroquiaAnzoategui($municipio->nombre, $nombre);
        $normalized = GeografiaNombreNormalizer::normalize($nombre);

        $existing = Parroquia::query()
            ->where('municipio_id', $municipio->id)
            ->get()
            ->first(fn (Parroquia $parroquia): bool => GeografiaNombreNormalizer::normalize($parroquia->nombre) === $normalized);

        if ($existing !== null) {
            $preferred = GeografiaCanonicalNames::preferParroquiaNombre($existing->nombre, $canonical, $canonical);

            if ($existing->nombre !== $preferred) {
                $existing->update(['nombre' => $preferred]);
            }

            return $existing;
        }

        return Parroquia::query()->create([
            'municipio_id' => $municipio->id,
            'nombre' => $canonical,
        ]);
    }
}
