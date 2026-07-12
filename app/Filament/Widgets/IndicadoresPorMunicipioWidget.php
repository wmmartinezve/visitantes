<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\OperacionMetricsService;
use App\Support\OperacionFiltros;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class IndicadoresPorMunicipioWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Indicadores por municipio';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => app(OperacionMetricsService::class)->resumenPorMunicipio(
                OperacionFiltros::fromArray($this->filters),
            ))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('municipio')
                    ->label('Municipio')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('hogares_solidarios')
                    ->label('Hogares')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('hogares_con_nucleo')
                    ->label('Con núcleo')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('hogares_nuevos_periodo')
                    ->label('Hogares nuevos')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('anfitriones_desplegados')
                    ->label('Anfitriones')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('invitados_activos')
                    ->label('Invitados activos')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('invitados_registrados')
                    ->label('Registrados período')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
