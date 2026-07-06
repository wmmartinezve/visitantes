<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RefugioResource\Pages;
use App\Filament\Support\GeolocalizacionFields;
use App\Filament\Support\ParroquiaSelectFields;
use App\Models\Refugio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RefugioResource extends Resource
{
    protected static ?string $model = Refugio::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Refugios';

    protected static ?string $modelLabel = 'refugio';

    protected static ?string $pluralModelLabel = 'refugios';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del refugio')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
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
                    ->label('Refugio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parroquia.municipio.nombre')
                    ->label('Municipio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parroquia.nombre')
                    ->label('Parroquia')
                    ->sortable(),
                Tables\Columns\TextColumn::make('direccion_exacta')
                    ->label('Dirección')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('latitud')
                    ->label('Ubicación')
                    ->formatStateUsing(fn ($state, Refugio $record): string => number_format((float) $record->latitud, 5).', '.number_format((float) $record->longitud, 5))
                    ->url(fn (Refugio $record): string => 'https://www.google.com/maps?q='.$record->latitud.','.$record->longitud)
                    ->openUrlInNewTab()
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
            'index' => Pages\ListRefugios::route('/'),
            'create' => Pages\CreateRefugio::route('/create'),
            'edit' => Pages\EditRefugio::route('/{record}/edit'),
        ];
    }
}
