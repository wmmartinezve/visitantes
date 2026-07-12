<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesWhenLogisticaDisabled;
use App\Filament\Resources\CentroAcopioResource\Pages;
use App\Filament\Resources\CentroAcopioResource\RelationManagers\InventariosRelationManager;
use App\Filament\Support\GeolocalizacionFields;
use App\Filament\Support\ParroquiaSelectFields;
use App\Models\CentroAcopio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CentroAcopioResource extends Resource
{
    use HidesWhenLogisticaDisabled;

    protected static ?string $model = CentroAcopio::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Centros de acopio';

    protected static ?string $modelLabel = 'centro de acopio';

    protected static ?string $pluralModelLabel = 'centros de acopio';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del centro')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('contacto')
                        ->label('Contacto')
                        ->maxLength(255),
                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true),
                    ...ParroquiaSelectFields::make($form->getRecord()),
                    ...GeolocalizacionFields::make(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Centro')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parroquia.municipio.nombre')
                    ->label('Municipio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parroquia.nombre')
                    ->label('Parroquia')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contacto')
                    ->label('Contacto')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('latitud')
                    ->label('Ubicación')
                    ->formatStateUsing(fn ($state, CentroAcopio $record): string => number_format((float) $record->latitud, 5).', '.number_format((float) $record->longitud, 5))
                    ->url(fn (CentroAcopio $record): string => 'https://www.google.com/maps?q='.$record->latitud.','.$record->longitud)
                    ->openUrlInNewTab()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('inventarios_count')
                    ->label('Ítems')
                    ->counts('inventarios')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),
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

    public static function getRelations(): array
    {
        return [
            InventariosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCentroAcopios::route('/'),
            'create' => Pages\CreateCentroAcopio::route('/create'),
            'edit' => Pages\EditCentroAcopio::route('/{record}/edit'),
        ];
    }
}
