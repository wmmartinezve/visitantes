<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TipoAnfitrionHogar;
use App\Enums\TipoViviendaHogar;
use App\Filament\Resources\HogarSolidarioResource\Pages;
use App\Filament\Support\GeografiaSelectFields;
use App\Filament\Support\GeolocalizacionFields;
use App\Filament\Support\HogarAnfitrionFields;
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del hogar solidario')
                ->description('El código se asigna automáticamente (municipio · parroquia · correlativo).')
                ->schema([
                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Select::make('tipo_vivienda')
                        ->label('Tipo de vivienda')
                        ->options(collect(TipoViviendaHogar::cases())->mapWithKeys(
                            fn (TipoViviendaHogar $tipo): array => [$tipo->value => $tipo->label()]
                        ))
                        ->required()
                        ->default(TipoViviendaHogar::Casa->value),
                    ...GeografiaSelectFields::hogar($form->getRecord()),
                    ...GeolocalizacionFields::make(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Tipo de acogida')
                ->schema([
                    ...HogarAnfitrionFields::make(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Responsable del hogar')
                ->schema([
                    Forms\Components\TextInput::make('responsable_nombre')
                        ->label('Nombre del responsable')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('responsable_cedula')
                        ->label('Cédula del responsable')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('responsable_telefono')
                        ->label('Teléfono del responsable')
                        ->tel()
                        ->maxLength(30),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_anfitrion')
                    ->label('Acogida')
                    ->badge()
                    ->formatStateUsing(fn (?TipoAnfitrionHogar $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('parentesco_anfitrion')
                    ->label('Parentesco')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tipo_vivienda')
                    ->label('Vivienda')
                    ->badge()
                    ->formatStateUsing(fn (?TipoViviendaHogar $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('parroquia.municipio.nombre')
                    ->label('Municipio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parroquia.nombre')
                    ->label('Parroquia')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comuna.nombre')
                    ->label('Comuna')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('responsable_nombre')
                    ->label('Responsable')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('direccion_exacta')
                    ->label('Dirección')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('jefeFamilia.nombre')
                    ->label('Núcleo familiar (jefe)')
                    ->formatStateUsing(fn (HogarSolidario $record): string => $record->jefeFamilia
                        ? trim($record->jefeFamilia->nombre.' '.$record->jefeFamilia->apellido)
                        : 'Sin registrar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invitados_count')
                    ->label('Personas en núcleo')
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
            ->with(['parroquia.municipio', 'comuna', 'jefeFamilia'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHogaresSolidarios::route('/'),
            'edit' => Pages\EditHogarSolidario::route('/{record}/edit'),
        ];
    }
}
