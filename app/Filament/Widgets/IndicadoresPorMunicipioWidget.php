<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Municipio;
use App\Services\OperacionMetricsService;
use App\Support\OperacionFiltros;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IndicadoresPorMunicipioWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Indicadores por municipio';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $filtros = OperacionFiltros::fromArray($this->filters);
        $resumen = $this->resumenIndexado($filtros);

        return $table
            ->query(fn (): Builder => $this->municipiosQuery($filtros))
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Municipio')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('hogares_solidarios')
                    ->label('Hogares')
                    ->formatStateUsing(fn ($state, Municipio $record): string => (string) ($resumen->get($record->id)?->hogares_solidarios ?? 0))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('hogares_con_nucleo')
                    ->label('Con núcleo')
                    ->formatStateUsing(fn ($state, Municipio $record): string => (string) ($resumen->get($record->id)?->hogares_con_nucleo ?? 0))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('hogares_nuevos_periodo')
                    ->label('Hogares nuevos')
                    ->formatStateUsing(fn ($state, Municipio $record): string => (string) ($resumen->get($record->id)?->hogares_nuevos_periodo ?? 0))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('anfitriones_desplegados')
                    ->label('Anfitriones')
                    ->formatStateUsing(fn ($state, Municipio $record): string => (string) ($resumen->get($record->id)?->anfitriones_desplegados ?? 0))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('invitados_activos')
                    ->label('Invitados activos')
                    ->formatStateUsing(fn ($state, Municipio $record): string => (string) ($resumen->get($record->id)?->invitados_activos ?? 0))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('invitados_registrados')
                    ->label('Registrados período')
                    ->formatStateUsing(fn ($state, Municipio $record): string => (string) ($resumen->get($record->id)?->invitados_registrados ?? 0))
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(false);
    }

    /** @return Collection<int, object> */
    private function resumenIndexado(OperacionFiltros $filtros): Collection
    {
        return app(OperacionMetricsService::class)
            ->resumenPorMunicipio($filtros)
            ->keyBy('municipio_id');
    }

    /** @return Builder<Municipio> */
    private function municipiosQuery(OperacionFiltros $filtros): Builder
    {
        return Municipio::query()
            ->whereHas('estado', fn (Builder $q) => $q->where('nombre', config('visitantes.estado')))
            ->when($filtros->municipioId, fn (Builder $q) => $q->whereKey($filtros->municipioId))
            ->when($filtros->parroquiaId, fn (Builder $q) => $q->whereHas(
                'parroquias',
                fn (Builder $pq) => $pq->whereKey($filtros->parroquiaId),
            ))
            ->orderBy('nombre');
    }
}
