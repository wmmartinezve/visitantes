<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TipoViviendaHogar;
use App\Filament\Resources\HogarSolidarioResource\Pages;
use App\Filament\Support\ComunaSelectFields;
use App\Filament\Support\GeolocalizacionFields;
use App\Models\HogarSolidario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HogarSolidarioResource extends Resource
{
    protected static ?string $model = HogarSolidario::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Hogares solidarios';

    protected static ?string $modelLabel = 'hogar solidario';

    protected static ?string $pluralModelLabel = 'hogares solidarios';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del hogar solidario')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('tipo_vivienda')
                        ->label('Tipo de vivienda')
                        ->options(collect(TipoViviendaHogar::cases())->mapWithKeys(
                            fn (TipoViviendaHogar $tipo): array => [$tipo->value => $tipo->label()]
                        ))
                        ->required()
                        ->default(TipoViviendaHogar::Casa->value),
                    ...ComunaSelectFields::make($form->getRecord()),
                    ...GeolocalizacionFields::make(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Responsable e habitantes')
                ->schema([
                    Forms\Components\TextInput::make('responsable_nombre')
                        ->label('Responsable del hogar')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('responsable_cedula')
                        ->label('Cédula del responsable')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('responsable_telefono')
                        ->label('Teléfono del responsable')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\Repeater::make('habitantes')
                        ->label('Habitantes del hogar solidario')
                        ->schema([
                            Forms\Components\TextInput::make('nombre')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('parentesco')
                                ->label('Parentesco / relación')
                                ->maxLength(50),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->addActionLabel('Agregar habitante'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Hogar solidario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_vivienda')
                    ->label('Vivienda')
                    ->badge()
                    ->formatStateUsing(fn (?TipoViviendaHogar $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('comuna.parroquia.municipio.nombre')
                    ->label('Municipio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comuna.parroquia.nombre')
                    ->label('Parroquia')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comuna.nombre')
                    ->label('Comuna')
                    ->sortable(),
                Tables\Columns\TextColumn::make('responsable_nombre')
                    ->label('Responsable')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('direccion_exacta')
                    ->label('Dirección')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('invitados_count')
                    ->label('Invitados')
                    ->counts('invitados')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHogaresSolidarios::route('/'),
            'create' => Pages\CreateHogarSolidario::route('/create'),
            'edit' => Pages\EditHogarSolidario::route('/{record}/edit'),
        ];
    }
}
