<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Anfitrion = 'anfitrion';
    case CentroAcopio = 'centro_acopio';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Anfitrion => 'Anfitrión',
            self::CentroAcopio => 'Centro de acopio',
        };
    }
}
