<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\RequerimientoEstatus;
use App\Models\Requerimiento;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RequerimientosPendientesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Requerimientos pendientes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Requerimiento::query()
                    ->with(['invitado.refugio', 'anfitrion'])
                    ->where('estatus', RequerimientoEstatus::Pendiente)
                    ->latest()
            )
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
                Tables\Columns\TextColumn::make('anfitrion.name')
                    ->label('Anfitrión'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado')
                    ->since(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
