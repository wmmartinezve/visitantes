<?php

declare(strict_types=1);

namespace App\Enums;

enum RequerimientoEstatus: string
{
    case Pendiente = 'pendiente';
    case Asignado = 'asignado';
    case Entregado = 'entregado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Asignado => 'Asignado',
            self::Entregado => 'Entregado',
        };
    }
}
