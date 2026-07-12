<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoViviendaHogar: string
{
    case Casa = 'casa';
    case Edificio = 'edificio';

    public function label(): string
    {
        return match ($this) {
            self::Casa => 'Casa',
            self::Edificio => 'Edificio',
        };
    }
}
