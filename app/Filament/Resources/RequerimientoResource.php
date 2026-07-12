<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Filament\Resources\RequerimientoResource\Pages;
use App\Models\CentroAcopio;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Models\User;
use App\Support\InsumoCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RequerimientoResource extends Resource
{
    protected static ?string $model = Requerimiento::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Logística';

    protected static ?string $navigationLabel = 'Requerimientos';

    protected static ?string $modelLabel = 'requerimiento';

    protected static ?string $pluralModelLabel = 'requerimientos';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('invitado_id')
                ->label('Invitado')
                ->relationship(
                    name: 'invitado',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn ($query) => $query->with('refugio')->orderBy('apellido'),
                )
                ->getOptionLabelFromRecordUsing(fn (Invitado $record): string => $record->nombreCompleto().' — '.$record->refugio?->codigo)
                ->searchable(['nombre', 'apellido', 'cedula'])
                ->preload()
                ->required(),
            Forms\Components\Select::make('anfitrion_id')
                ->label('Anfitrión')
                ->options(fn (): array => User::query()
                    ->where('rol', UserRole::Anfitrion)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->required(),
            Forms\Components\Select::make('categoria')
                ->label('Categoría')
                ->options(fn (): array => array_combine(InsumoCatalog::categorias(), InsumoCatalog::categorias()))
                ->searchable()
                ->live()
                ->required(),
            Forms\Components\Select::make('subcategoria')
                ->label('Subcategoría')
                ->options(fn (Get $get): array => array_combine(
                    InsumoCatalog::subcategorias((string) $get('categoria')),
                    InsumoCatalog::subcategorias((string) $get('categoria')),
                ) ?: [])
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('item_solicitado')
                ->label('Etiqueta')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn (Get $get, ?Requerimiento $record): string => $record?->item_solicitado
                    ?? ($get('categoria') && $get('subcategoria')
                        ? InsumoCatalog::etiqueta((string) $get('categoria'), (string) $get('subcategoria'))
                        : '—')),
            Forms\Components\TextInput::make('cantidad')
                ->label('Cantidad')
                ->required()
                ->numeric()
                ->minValue(1)
                ->default(1),
            Forms\Components\Select::make('estatus')
                ->label('Estatus')
                ->options(collect(RequerimientoEstatus::cases())->mapWithKeys(
                    fn (RequerimientoEstatus $estatus): array => [$estatus->value => $estatus->label()]
                ))
                ->default(RequerimientoEstatus::Pendiente->value)
                ->required()
                ->live(),
            Forms\Components\Select::make('centro_acopio_id')
                ->label('Centro de acopio asignado')
                ->options(fn (): array => CentroAcopio::query()
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id')
                    ->all())
                ->searchable()
                ->visible(fn (Get $get): bool => in_array($get('estatus'), [
                    RequerimientoEstatus::Asignado->value,
                    RequerimientoEstatus::Entregado->value,
                ], true))
                ->required(fn (Get $get): bool => in_array($get('estatus'), [
                    RequerimientoEstatus::Asignado->value,
                    RequerimientoEstatus::Entregado->value,
                ], true)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invitado.nombre')
                    ->label('Invitado')
                    ->formatStateUsing(fn ($state, Requerimiento $record): string => $record->invitado?->nombreCompleto() ?? '—')
                    ->searchable(['invitado.nombre', 'invitado.apellido']),
                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoría')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subcategoria')
                    ->label('Subcategoría')
                    ->searchable(),
                Tables\Columns\TextColumn::make('item_solicitado')
                    ->label('Etiqueta')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->formatStateUsing(fn (?RequerimientoEstatus $state): string => $state?->label() ?? '—')
                    ->color(fn (?RequerimientoEstatus $state): string => match ($state) {
                        RequerimientoEstatus::Pendiente => 'warning',
                        RequerimientoEstatus::Asignado => 'info',
                        RequerimientoEstatus::Entregado => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('centroAcopio.nombre')
                    ->label('Centro asignado')
                    ->placeholder('Sin asignar'),
                Tables\Columns\TextColumn::make('anfitrion.name')
                    ->label('Anfitrión')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options(collect(RequerimientoEstatus::cases())->mapWithKeys(
                        fn (RequerimientoEstatus $estatus): array => [$estatus->value => $estatus->label()]
                    )),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequerimientos::route('/'),
            'create' => Pages\CreateRequerimiento::route('/create'),
            'edit' => Pages\EditRequerimiento::route('/{record}/edit'),
        ];
    }
}
