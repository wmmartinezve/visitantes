<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitadoEstatus: string
{
    case Activo = 'activo';
    case Egresado = 'egresado';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Egresado => 'Egresado',
        };
    }
}
