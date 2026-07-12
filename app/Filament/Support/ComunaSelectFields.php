<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Comuna;
use App\Models\Municipio;
use App\Models\Parroquia;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;

final class ComunaSelectFields
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function make(?Model $record = null, ?string $syncProcedenciaPrefix = null): array
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
                ->afterStateUpdated(function (Set $set, ?int $state) use ($syncProcedenciaPrefix): void {
                    $set('parroquia_id', null);
                    $set('comuna_id', null);

                    if ($syncProcedenciaPrefix === null || $state === null) {
                        return;
                    }

                    $municipio = Municipio::query()->find($state);
                    if ($municipio === null) {
                        return;
                    }

                    $set("{$syncProcedenciaPrefix}_estado_id", $municipio->estado_id);
                    $set("{$syncProcedenciaPrefix}_municipio_id", $state);
                    $set("{$syncProcedenciaPrefix}_parroquia_id", null);
                })
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
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, ?int $state) use ($syncProcedenciaPrefix): void {
                    $set('comuna_id', null);

                    if ($syncProcedenciaPrefix !== null && $state !== null) {
                        self::applyProcedenciaFromParroquia($set, $state, $syncProcedenciaPrefix);
                    }
                }),

            Forms\Components\Select::make('comuna_id')
                ->label('Comuna')
                ->options(function (Get $get): array {
                    $parroquiaId = $get('parroquia_id');

                    if (! $parroquiaId) {
                        return [];
                    }

                    return Comuna::query()
                        ->where('parroquia_id', $parroquiaId)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all();
                })
                ->searchable()
                ->placeholder('Opcional'),
        ];
    }

    public static function syncProcedenciaFromHogar(Set $set, Get $get, string $prefix, bool $onlyIfEmpty = false): void
    {
        $parroquiaId = $get('parroquia_id');

        if ($parroquiaId === null) {
            return;
        }

        if ($onlyIfEmpty && filled($get("{$prefix}_parroquia_id"))) {
            return;
        }

        self::applyProcedenciaFromParroquia($set, (int) $parroquiaId, $prefix);
    }

    private static function applyProcedenciaFromParroquia(Set $set, int $parroquiaId, string $prefix): void
    {
        $parroquia = Parroquia::query()->with('municipio')->find($parroquiaId);

        if ($parroquia?->municipio === null) {
            return;
        }

        $set("{$prefix}_estado_id", $parroquia->municipio->estado_id);
        $set("{$prefix}_municipio_id", $parroquia->municipio_id);
        $set("{$prefix}_parroquia_id", $parroquia->id);
    }
}
