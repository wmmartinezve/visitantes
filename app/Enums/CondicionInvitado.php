<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Validation\Rule;

enum CondicionInvitado: string
{
    case Ninguna = 'ninguna';
    case Discapacidad = 'discapacidad';
    case Embarazada = 'embarazada';
    case AdultoMayor = 'adulto_mayor';

    public function label(): string
    {
        return match ($this) {
            self::Ninguna => 'Ninguna',
            self::Discapacidad => 'Discapacidad',
            self::Embarazada => 'Embarazada',
            self::AdultoMayor => 'Adulto mayor',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<int, mixed> */
    public static function validationRules(bool $required = true): array
    {
        $rules = ['string', Rule::in(self::values())];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }
}
