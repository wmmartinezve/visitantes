<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * @deprecated Use GeografiaSelectFields::procedencia() instead.
 */
final class ProcedenciaSelectFields
{
    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function make(string $prefix = 'procedencia_'): array
    {
        return GeografiaSelectFields::procedencia($prefix);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function makeGrouped(string $statePath = 'procedencia'): array
    {
        return GeografiaSelectFields::procedencia($statePath.'_');
    }
}
