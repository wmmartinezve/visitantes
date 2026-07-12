<?php

declare(strict_types=1);

namespace App\Enums;

enum SituacionJefeFamilia: string
{
    case Trabajando = 'trabajando';
    case Desempleado = 'desempleado';
    case Pensionado = 'pensionado';
    case Estudiante = 'estudiante';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Trabajando => 'Trabajando',
            self::Desempleado => 'Desempleado',
            self::Pensionado => 'Pensionado',
            self::Estudiante => 'Estudiante',
            self::Otro => 'Otro',
        };
    }
}
