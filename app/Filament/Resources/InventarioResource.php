<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InventarioResource\Pages;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Support\InsumoCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Logística';

    protected static ?string $navigationLabel = 'Inventario global';

    protected static ?string $modelLabel = 'inventario';

    protected static ?string $pluralModelLabel = 'inventarios';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('centro_acopio_id')
                ->label('Centro de acopio')
                ->relationship('centroAcopio', 'nombre')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('categoria')
                ->label('Categoría')
                ->options(fn (): array => array_combine(InsumoCatalog::categorias(), InsumoCatalog::categorias()))
                ->searchable()
                ->live()
                ->required(),
            Forms\Components\Select::make('subcategoria')
                ->label('Subcategoría')
                ->options(fn (Forms\Get $get): array => ($subs = InsumoCatalog::subcategorias((string) $get('categoria')))
                    ? array_combine($subs, $subs)
                    : [])
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('item_nombre')
                ->label('Etiqueta')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn (Forms\Get $get, ?Inventario $record): string => $record?->item_nombre
                    ?? ($get('categoria') && $get('subcategoria')
                        ? InsumoCatalog::etiqueta((string) $get('categoria'), (string) $get('subcategoria'))
                        : '—')),
            Forms\Components\TextInput::make('cantidad')
                ->label('Cantidad')
                ->required()
                ->numeric()
                ->minValue(0),
            Forms\Components\TextInput::make('unidad_medida')
                ->label('Unidad de medida')
                ->required()
                ->maxLength(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('centroAcopio.nombre')
                    ->label('Centro de acopio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('centroAcopio.parroquia.municipio.nombre')
                    ->label('Municipio')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoría')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subcategoria')
                    ->label('Subcategoría')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_nombre')
                    ->label('Etiqueta')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable()
                    ->color(fn (Inventario $record): string => $record->cantidad <= 5 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('centro_acopio_id')
                    ->label('Centro de acopio')
                    ->options(fn (): array => CentroAcopio::query()->orderBy('nombre')->pluck('nombre', 'id')->all()),
                Tables\Filters\Filter::make('stock_bajo')
                    ->label('Stock bajo (≤ 5)')
                    ->query(fn ($query) => $query->where('cantidad', '<=', 5)),
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
            'index' => Pages\ListInventarios::route('/'),
            'create' => Pages\CreateInventario::route('/create'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }
}
