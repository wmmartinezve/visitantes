<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Support\NucleoFamiliarPorHogar;

trait CreaInvitadosDePrueba
{
    /** @param  array<string, mixed>  $attrs */
    protected function crearInvitadoParaHogar(int $hogarId, array $attrs = []): Invitado
    {
        $jefe = NucleoFamiliarPorHogar::jefeEnHogar($hogarId);

        $defaults = [
            'nombre' => 'Test',
            'apellido' => 'Persona',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogarId,
            'estatus' => 'activo',
        ];

        if ($jefe !== null && ! array_key_exists('jefe_familia_id', $attrs)) {
            $defaults['jefe_familia_id'] = $jefe->id;
            $defaults['parentesco'] = $attrs['parentesco'] ?? 'Hijo(a)';
        } else {
            $defaults['jefe_familia_id'] = $attrs['jefe_familia_id'] ?? null;
        }

        return Invitado::query()->create(array_merge($defaults, $attrs));
    }

    protected function limpiarNucleoDeHogar(int $hogarId): void
    {
        Requerimiento::query()
            ->whereHas('invitado', fn ($q) => $q->where('hogar_solidario_id', $hogarId))
            ->delete();

        Invitado::query()->where('hogar_solidario_id', $hogarId)->forceDelete();
    }
}
