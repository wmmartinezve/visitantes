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
                ->options(fn (): array => Estado::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
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
                ->options(function (Get $get) use ($estadoField): array {
                    $estadoId = $get($estadoField);

                    if (! $estadoId) {
                        return [];
                    }

                    return Municipio::query()
                        ->where('estado_id', $estadoId)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all();
                })
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set($parroquiaField, null)),

            Forms\Components\Select::make($parroquiaField)
                ->label('Parroquia de procedencia')
                ->options(function (Get $get) use ($municipioField): array {
                    $municipioId = $get($municipioField);

                    if (! $municipioId) {
                        return [];
                    }

                    return Parroquia::query()
                        ->where('municipio_id', $municipioId)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all();
                })
                ->searchable()
                ->required(),
        ];
    }
}
