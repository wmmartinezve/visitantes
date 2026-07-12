<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

final class ProcedenciaSelectFields
{
    /**
     * Selects en cascada: todos los estados de Venezuela → municipios → parroquias.
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function make(string $prefix = 'procedencia'): array
    {
        $estadoField = $prefix.'_estado_id';
        $municipioField = $prefix.'_municipio_id';
        $parroquiaField = $prefix.'_parroquia_id';

        return [
            Forms\Components\Select::make($estadoField)
                ->label('Estado de procedencia')
                ->options(fn (): array => GeografiaSelectOptions::estados())
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(function (Set $set) use ($municipioField, $parroquiaField): void {
                    $set($municipioField, null);
                    $set($parroquiaField, null);
                }),

            Forms\Components\Select::make($municipioField)
                ->label('Municipio de procedencia')
                ->options(fn (Get $get): array => GeografiaSelectOptions::municipios($get, $estadoField, $municipioField))
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set($parroquiaField, null)),

            Forms\Components\Select::make($parroquiaField)
                ->label('Parroquia de procedencia')
                ->options(fn (Get $get): array => GeografiaSelectOptions::parroquias($get, $municipioField, $parroquiaField))
                ->searchable()
                ->preload()
                ->required(),
        ];
    }
}
