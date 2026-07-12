<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoAnfitrionHogar: string
{
    case Familiar = 'familiar';
    case Amigo = 'amigo';

    public function label(): string
    {
        return match ($this) {
            self::Familiar => 'Familiar',
            self::Amigo => 'Amigo',
        };
    }
}
