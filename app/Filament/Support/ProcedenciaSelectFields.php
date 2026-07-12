<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

final class ProcedenciaSelectFields
{
    /**
     * Campos planos (p. ej. InvitadoResource): procedencia_estado_id, etc.
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function make(string $prefix = 'procedencia'): array
    {
        return self::schema(
            estadoField: $prefix.'_estado_id',
            municipioField: $prefix.'_municipio_id',
            parroquiaField: $prefix.'_parroquia_id',
        );
    }

    /**
     * Grupo anidado para wizards (p. ej. data.jefe_procedencia.estado_id).
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function makeGrouped(string $statePath = 'procedencia'): array
    {
        return [
            Forms\Components\Group::make()
                ->statePath($statePath)
                ->columns(2)
                ->columnSpanFull()
                ->schema(self::schema(
                    estadoField: 'estado_id',
                    municipioField: 'municipio_id',
                    parroquiaField: 'parroquia_id',
                )),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    private static function schema(string $estadoField, string $municipioField, string $parroquiaField): array
    {
        return [
            Forms\Components\Select::make($estadoField)
                ->label('Estado de procedencia')
                ->options(fn (): array => GeografiaSelectOptions::estados())
                ->searchable()
                ->required()
                ->live()
                ->helperText('Seleccione el estado de procedencia del Invitado (puede ser cualquier estado de Venezuela).')
                ->afterStateUpdated(function (Set $set) use ($municipioField, $parroquiaField): void {
                    $set($municipioField, null);
                    $set($parroquiaField, null);
                }),

            Forms\Components\Select::make($municipioField)
                ->label('Municipio de procedencia')
                ->options(fn (Get $get): array => GeografiaSelectOptions::municipios($get, $estadoField))
                ->searchable()
                ->required()
                ->live()
                ->placeholder(fn (Get $get): string => filled($get($estadoField))
                    ? 'Seleccione un municipio'
                    : 'Primero seleccione el estado')
                ->afterStateUpdated(fn (Set $set) => $set($parroquiaField, null)),

            Forms\Components\Select::make($parroquiaField)
                ->label('Parroquia de procedencia')
                ->options(fn (Get $get): array => GeografiaSelectOptions::parroquias($get, $municipioField))
                ->searchable()
                ->required()
                ->placeholder(fn (Get $get): string => filled($get($municipioField))
                    ? 'Seleccione una parroquia'
                    : 'Primero seleccione el municipio'),
        ];
    }
}
