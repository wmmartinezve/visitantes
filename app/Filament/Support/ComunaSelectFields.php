<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * @deprecated Use GeografiaSelectFields::hogar() instead.
 */
final class ComunaSelectFields
{
    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function make(?\Illuminate\Database\Eloquent\Model $record = null): array
    {
        return GeografiaSelectFields::hogar($record);
    }
}
