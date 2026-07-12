<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\TipoAnfitrionHogar;
use Filament\Forms;
use Filament\Forms\Get;

final class HogarAnfitrionFields
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function make(string $prefix = ''): array
    {
        $tipoField = $prefix.'tipo_anfitrion';
        $parentescoField = $prefix.'parentesco_anfitrion';

        return [
            Forms\Components\Select::make($tipoField)
                ->label('¿Quién recibe al Invitado en este hogar?')
                ->options(collect(TipoAnfitrionHogar::cases())->mapWithKeys(
                    fn (TipoAnfitrionHogar $tipo): array => [$tipo->value => $tipo->label()]
                ))
                ->default(TipoAnfitrionHogar::Familiar->value)
                ->required()
                ->live(),

            Forms\Components\Select::make($parentescoField)
                ->label('Parentesco con el jefe de familia')
                ->options(collect(config('visitantes.parentescos'))->mapWithKeys(
                    fn (string $p): array => [$p => $p]
                ))
                ->searchable()
                ->visible(fn (Get $get): bool => $get($tipoField) === TipoAnfitrionHogar::Familiar->value)
                ->required(fn (Get $get): bool => $get($tipoField) === TipoAnfitrionHogar::Familiar->value)
                ->dehydrated(fn (Get $get): bool => $get($tipoField) === TipoAnfitrionHogar::Familiar->value),
        ];
    }
}
