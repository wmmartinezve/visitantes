<?php

declare(strict_types=1);

namespace App\Support;

final class VisitantesFeatures
{
    /**
     * Logística: centros de acopio, inventario, requerimientos y entregas.
     */
    public static function logistica(): bool
    {
        return (bool) config('visitantes.features.logistica', false);
    }
}
