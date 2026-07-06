<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms;
use Filament\Forms\Components\ViewField;

final class GeolocalizacionFields
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function make(): array
    {
        return [
            Forms\Components\Textarea::make('direccion_exacta')
                ->label('Dirección exacta')
                ->required()
                ->columnSpanFull(),

            ViewField::make('geolocalizacion_gps')
                ->view('filament.forms.geolocalizacion-button')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('latitud')
                ->label('Latitud')
                ->required()
                ->numeric()
                ->step('0.00000001')
                ->helperText('Puedes usar el botón GPS o ingresar manualmente.'),

            Forms\Components\TextInput::make('longitud')
                ->label('Longitud')
                ->required()
                ->numeric()
                ->step('0.00000001'),
        ];
    }
}
