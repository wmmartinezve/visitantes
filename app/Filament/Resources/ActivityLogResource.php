<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ActivityAction;
use App\Enums\ActivityChannel;
use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use App\Models\Invitado;
use App\Models\Inventario;
use App\Models\Requerimiento;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Bitácora';

    protected static ?string $modelLabel = 'registro de bitácora';

    protected static ?string $pluralModelLabel = 'bitácora';

    protected static ?int $navigationSort = 90;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_role')
                    ->label('Rol')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'admin' => 'Administrador',
                        'anfitrion' => 'Anfitrión',
                        'centro_acopio' => 'Centro de acopio',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('action')
                    ->label('Acción')
                    ->badge()
                    ->formatStateUsing(fn (?ActivityAction $state): string => $state?->label() ?? '—')
                    ->color(fn (?ActivityAction $state): string => match ($state) {
                        ActivityAction::Created => 'success',
                        ActivityAction::Updated, ActivityAction::ProfileUpdated => 'info',
                        ActivityAction::Deleted, ActivityAction::ForceDeleted => 'danger',
                        ActivityAction::Restored => 'warning',
                        ActivityAction::Entregado, ActivityAction::Asignado => 'primary',
                        ActivityAction::FotoAttached => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('channel')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (?ActivityChannel $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Entidad')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Invitado::class => 'Invitado',
                        Requerimiento::class => 'Requerimiento',
                        Inventario::class => 'Inventario',
                        User::class => 'Usuario',
                        default => class_basename((string) $state),
                    }),
                Tables\Columns\TextColumn::make('subject_label')
                    ->label('Referencia')
                    ->state(fn (ActivityLog $record): string => $record->subjectLabel()),
                Tables\Columns\TextColumn::make('description')
                    ->label('Detalle')
                    ->wrap()
                    ->limit(80),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Acción')
                    ->options(collect(ActivityAction::cases())->mapWithKeys(
                        fn (ActivityAction $action): array => [$action->value => $action->label()]
                    )),
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Origen')
                    ->options(collect(ActivityChannel::cases())->mapWithKeys(
                        fn (ActivityChannel $channel): array => [$channel->value => $channel->label()]
                    )),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Entidad')
                    ->options([
                        Invitado::class => 'Invitado',
                        Requerimiento::class => 'Requerimiento',
                        Inventario::class => 'Inventario',
                        User::class => 'Usuario',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde')->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['hasta'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalle de bitácora')
                    ->infolist([
                        \Filament\Infolists\Components\TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                        \Filament\Infolists\Components\TextEntry::make('user.name')->label('Usuario')->placeholder('Sistema'),
                        \Filament\Infolists\Components\TextEntry::make('action')->label('Acción')->formatStateUsing(fn ($state) => $state?->label()),
                        \Filament\Infolists\Components\TextEntry::make('channel')->label('Origen')->formatStateUsing(fn ($state) => $state?->label()),
                        \Filament\Infolists\Components\TextEntry::make('description')->label('Descripción')->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('properties')
                            ->label('Datos')
                            ->formatStateUsing(fn (?array $state): string => $state !== null
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : '—')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'font-mono text-xs whitespace-pre-wrap']),
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
