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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class IndicadoresPorMunicipioWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Indicadores por municipio';

    protected int|string|array $columnSpan = 'full';

    /** @var Collection<int|string, object>|null */
    private ?Collection $resumenCache = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->municipiosQuery(OperacionFiltros::fromArray($this->filters)))
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Municipio')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('hogares_solidarios')
                    ->label('Hogares')
                    ->getStateUsing(fn (Model $record): string => $this->valorIndicador($record, 'hogares_solidarios'))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('hogares_con_nucleo')
                    ->label('Con núcleo')
                    ->getStateUsing(fn (Model $record): string => $this->valorIndicador($record, 'hogares_con_nucleo'))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('hogares_nuevos_periodo')
                    ->label('Hogares nuevos')
                    ->getStateUsing(fn (Model $record): string => $this->valorIndicador($record, 'hogares_nuevos_periodo'))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('anfitriones_desplegados')
                    ->label('Anfitriones')
                    ->getStateUsing(fn (Model $record): string => $this->valorIndicador($record, 'anfitriones_desplegados'))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('invitados_activos')
                    ->label('Invitados activos')
                    ->getStateUsing(fn (Model $record): string => $this->valorIndicador($record, 'invitados_activos'))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('invitados_registrados')
                    ->label('Registrados período')
                    ->getStateUsing(fn (Model $record): string => $this->valorIndicador($record, 'invitados_registrados'))
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invitados_con_menciones')
                    ->label('Con menciones')
                    ->getStateUsing(fn (Model $record): string => $this->valorIndicador($record, 'invitados_con_menciones'))
                    ->alignCenter(),
            ])
            ->paginated(false);
    }

    private function valorIndicador(Model $record, string $campo): string
    {
        $fila = $this->resumenIndexado()->get($record->getKey());

        return (string) ($fila?->{$campo} ?? 0);
    }

    /** @return Collection<int|string, object> */
    private function resumenIndexado(): Collection
    {
        if ($this->resumenCache !== null) {
            return $this->resumenCache;
        }

        $filtros = OperacionFiltros::fromArray($this->filters);

        return $this->resumenCache = app(OperacionMetricsService::class)
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
