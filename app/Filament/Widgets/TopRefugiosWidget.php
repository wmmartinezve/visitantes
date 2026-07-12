<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\HogarSolidario;
use App\Support\OperacionFiltros;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopRefugiosWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected static ?string $heading = 'Hogares solidarios con más Invitados activos';

    public function table(Table $table): Table
    {
        $filtros = OperacionFiltros::fromArray($this->filters);

        return $table
            ->query(function () use ($filtros): Builder {
                return HogarSolidario::query()
                    ->when($filtros->refugioId, fn (Builder $q) => $q->whereKey($filtros->refugioId))
                    ->when($filtros->parroquiaId, fn (Builder $q) => $q->where('parroquia_id', $filtros->parroquiaId))
                    ->when($filtros->municipioId, fn (Builder $q) => $q->whereHas(
                        'parroquia',
                        fn (Builder $pq) => $pq->where('municipio_id', $filtros->municipioId),
                    ))
                    ->withCount(['invitados as activos_count' => fn (Builder $q) => $q->where('estatus', 'activo')])
                    ->orderByDesc('activos_count')
                    ->limit(10);
            })
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Hogar solidario'),
                Tables\Columns\TextColumn::make('parroquia.nombre')
                    ->label('Parroquia'),
                Tables\Columns\TextColumn::make('activos_count')
                    ->label('Invitados activos')
                    ->numeric(),
            ])
            ->paginated(false);
    }
}
