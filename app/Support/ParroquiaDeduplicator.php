<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Comuna;
use App\Models\Municipio;
use App\Models\Parroquia;
use Illuminate\Support\Facades\DB;

final class ParroquiaDeduplicator
{
    public function run(): int
    {
        $merged = 0;

        Municipio::query()
            ->with('parroquias')
            ->orderBy('id')
            ->each(function (Municipio $municipio) use (&$merged): void {
                /** @var array<string, list<Parroquia>> $groups */
                $groups = [];

                foreach ($municipio->parroquias as $parroquia) {
                    $key = GeografiaNombreNormalizer::normalize($parroquia->nombre);
                    $groups[$key][] = $parroquia;
                }

                foreach ($groups as $parroquias) {
                    if (count($parroquias) < 2) {
                        continue;
                    }

                    $merged += $this->mergeGroup($municipio, $parroquias);
                }
            });

        return $merged;
    }

    /**
     * @param  list<Parroquia>  $parroquias
     */
    private function mergeGroup(Municipio $municipio, array $parroquias): int
    {
        $preferredNombre = $parroquias[0]->nombre;

        foreach ($parroquias as $parroquia) {
            $canonical = GeografiaCanonicalNames::parroquiaAnzoategui($municipio->nombre, $parroquia->nombre);
            $preferredNombre = GeografiaCanonicalNames::preferParroquiaNombre(
                $preferredNombre,
                $parroquia->nombre,
                GeografiaNombreNormalizer::equals($canonical, $parroquia->nombre) ? $canonical : null,
            );
            $preferredNombre = GeografiaCanonicalNames::preferParroquiaNombre($preferredNombre, $canonical, $canonical);
        }

        usort($parroquias, fn (Parroquia $a, Parroquia $b): int => $a->id <=> $b->id);

        $keeper = $parroquias[0];

        foreach ($parroquias as $parroquia) {
            if ($parroquia->nombre === $preferredNombre) {
                $keeper = $parroquia;
                break;
            }
        }

        $duplicates = array_values(array_filter(
            $parroquias,
            fn (Parroquia $parroquia): bool => $parroquia->id !== $keeper->id,
        ));

        DB::transaction(function () use ($keeper, $duplicates, $preferredNombre): void {
            if ($keeper->nombre !== $preferredNombre) {
                $keeper->update(['nombre' => $preferredNombre]);
            }

            foreach ($duplicates as $duplicate) {
                $this->reassignParroquiaReferences($keeper, $duplicate);
                $duplicate->delete();
            }
        });

        return count($duplicates);
    }

    private function reassignParroquiaReferences(Parroquia $keeper, Parroquia $duplicate): void
    {
        DB::table('hogares_solidarios')->where('parroquia_id', $duplicate->id)->update(['parroquia_id' => $keeper->id]);
        DB::table('centros_acopio')->where('parroquia_id', $duplicate->id)->update(['parroquia_id' => $keeper->id]);
        DB::table('invitados')->where('procedencia_parroquia_id', $duplicate->id)->update(['procedencia_parroquia_id' => $keeper->id]);

        Comuna::query()
            ->where('parroquia_id', $duplicate->id)
            ->orderBy('id')
            ->each(function (Comuna $comuna) use ($keeper): void {
                $existing = Comuna::query()
                    ->where('parroquia_id', $keeper->id)
                    ->where('nombre', $comuna->nombre)
                    ->first();

                if ($existing === null) {
                    $comuna->update(['parroquia_id' => $keeper->id]);

                    return;
                }

                DB::table('hogares_solidarios')->where('comuna_id', $comuna->id)->update(['comuna_id' => $existing->id]);
                $comuna->delete();
            });
    }
}
