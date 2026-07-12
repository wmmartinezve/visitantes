<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\HogarSolidario;
use App\Models\Parroquia;
use Illuminate\Support\Str;

final class HogarSolidarioCodigoGenerator
{
    /**
     * Genera un código único: MUN-PAR-0001 (municipio · parroquia · correlativo en la parroquia).
     */
    public function generar(int $parroquiaId): string
    {
        $parroquia = Parroquia::query()
            ->with('municipio')
            ->findOrFail($parroquiaId);

        $municipioAbrev = $this->abreviatura($parroquia->municipio->nombre);
        $parroquiaAbrev = $this->abreviatura($parroquia->nombre);

        $secuencia = (int) HogarSolidario::withTrashed()
            ->where('parroquia_id', $parroquiaId)
            ->count() + 1;

        do {
            $codigo = sprintf('%s-%s-%04d', $municipioAbrev, $parroquiaAbrev, $secuencia);
            $secuencia++;
        } while (HogarSolidario::withTrashed()->where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Asigna código a hogares existentes (migración / mantenimiento).
     */
    public function asignarCodigosFaltantes(): int
    {
        $asignados = 0;

        HogarSolidario::withTrashed()
            ->with('parroquia.municipio')
            ->where(function ($query): void {
                $query->whereNull('codigo')->orWhere('codigo', '');
            })
            ->orderBy('id')
            ->each(function (HogarSolidario $hogar) use (&$asignados): void {
                if ($hogar->parroquia_id === null) {
                    return;
                }

                $hogar->forceFill([
                    'codigo' => $this->generar((int) $hogar->parroquia_id),
                ])->saveQuietly();

                $asignados++;
            });

        return $asignados;
    }

    private function abreviatura(string $nombre, int $maxLen = 3): string
    {
        $ascii = Str::upper(Str::ascii(trim($nombre)));
        $palabras = preg_split('/\s+/', $ascii) ?: [];

        if (count($palabras) >= 2) {
            $iniciales = implode('', array_map(
                fn (string $palabra): string => $palabra !== '' ? $palabra[0] : '',
                $palabras,
            ));

            $iniciales = preg_replace('/[^A-Z]/', '', $iniciales) ?? '';

            if (strlen($iniciales) >= 2) {
                return substr($iniciales, 0, $maxLen);
            }
        }

        $soloLetras = preg_replace('/[^A-Z]/', '', $ascii) ?? '';

        return substr($soloLetras, 0, $maxLen) ?: 'XXX';
    }
}
