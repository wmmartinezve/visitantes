<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Requerimiento;
use App\Support\OperacionFiltros;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RequerimientosFiltradosWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Requerimientos del período';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $filtros = OperacionFiltros::fromArray($this->filters);

                return Requerimiento::query()
                    ->with(['invitado.refugio', 'centroAcopio', 'anfitrion'])
                    ->when(true, function (Builder $query) use ($filtros): void {
                        if ($filtros->centroAcopioId) {
                            $query->where('centro_acopio_id', $filtros->centroAcopioId);
                        }

                        if ($filtros->refugioId) {
                            $query->whereHas('invitado', fn (Builder $q) => $q->where('refugio_id', $filtros->refugioId));
                        } elseif ($filtros->parroquiaId) {
                            $query->whereHas('invitado.refugio', fn (Builder $q) => $q->where('parroquia_id', $filtros->parroquiaId));
                        } elseif ($filtros->municipioId) {
                            $query->whereHas('invitado.refugio.parroquia', fn (Builder $q) => $q->where('municipio_id', $filtros->municipioId));
                        }

                        $query->whereBetween('created_at', [$filtros->desde, $filtros->hasta]);
                    })
                    ->latest();
            })
            ->columns([
                Tables\Columns\TextColumn::make('invitado.nombre')
                    ->label('Invitado')
                    ->formatStateUsing(fn ($state, Requerimiento $record): string => $record->invitado?->nombreCompleto() ?? '—'),
                Tables\Columns\TextColumn::make('invitado.refugio.nombre')
                    ->label('Refugio'),
                Tables\Columns\TextColumn::make('item_solicitado')
                    ->label('Ítem'),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad'),
                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge(),
                Tables\Columns\TextColumn::make('centroAcopio.nombre')
                    ->label('Centro')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('anfitrion.name')
                    ->label('Anfitrión'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }
}
