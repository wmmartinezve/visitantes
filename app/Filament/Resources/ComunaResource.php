<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ComunaResource\Pages;
use App\Models\Comuna;
use App\Models\Parroquia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ComunaResource extends Resource
{
    protected static ?string $model = Comuna::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Territorio';

    protected static ?string $navigationLabel = 'Comunas';

    protected static ?string $modelLabel = 'comuna';

    protected static ?string $pluralModelLabel = 'comunas';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('municipio_id')
                ->label('Municipio')
                ->options(fn (): array => \App\Models\Municipio::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                ->searchable()
                ->preload()
                ->live()
                ->dehydrated(false)
                ->required()
                ->afterStateUpdated(fn (Forms\Set $set) => $set('parroquia_id', null))
                ->default(fn (?Comuna $record): ?int => $record?->parroquia?->municipio_id),
            Forms\Components\Select::make('parroquia_id')
                ->label('Parroquia')
                ->options(function (Get $get): array {
                    $municipioId = $get('municipio_id');

                    if (! $municipioId) {
                        return [];
                    }

                    return Parroquia::query()
                        ->where('municipio_id', $municipioId)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all();
                })
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre de la comuna')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parroquia.municipio.nombre')->label('Municipio')->sortable(),
                Tables\Columns\TextColumn::make('parroquia.nombre')->label('Parroquia')->sortable(),
                Tables\Columns\TextColumn::make('nombre')->label('Comuna')->searchable()->sortable(),
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
            'index' => Pages\ListComunas::route('/'),
            'create' => Pages\CreateComuna::route('/create'),
            'edit' => Pages\EditComuna::route('/{record}/edit'),
        ];
    }
}
