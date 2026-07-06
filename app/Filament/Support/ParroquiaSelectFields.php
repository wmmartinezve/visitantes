<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Municipio;
use App\Models\Parroquia;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;

final class ParroquiaSelectFields
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function make(?Model $record = null): array
    {
        return [
            Forms\Components\Select::make('municipio_id')
                ->label('Municipio')
                ->options(fn (): array => Municipio::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                ->searchable()
                ->preload()
                ->live()
                ->dehydrated(false)
                ->required()
                ->afterStateUpdated(fn (Set $set) => $set('parroquia_id', null))
                ->default(fn (?Model $record): ?int => $record?->parroquia?->municipio_id),

            Forms\Components\Select::make('parroquia_id')
                ->label('Parroquia')
                ->options(function (Get $get): array {
                    $municipioId = $get('municipio_id');

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
