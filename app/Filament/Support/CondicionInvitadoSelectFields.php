<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\CondicionInvitado;
use Filament\Forms;

final class CondicionInvitadoSelectFields
{
    public static function make(string $field = 'condicion'): Forms\Components\Select
    {
        return Forms\Components\Select::make($field)
            ->label('Condición')
            ->options(collect(CondicionInvitado::cases())->mapWithKeys(
                fn (CondicionInvitado $condicion): array => [$condicion->value => $condicion->label()]
            ))
            ->default(CondicionInvitado::Ninguna->value)
            ->required();
    }
}
