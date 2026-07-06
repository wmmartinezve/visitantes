<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ParroquiaResource\Pages;
use App\Models\Municipio;
use App\Models\Parroquia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ParroquiaResource extends Resource
{
    protected static ?string $model = Parroquia::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Territorio';

    protected static ?string $navigationLabel = 'Parroquias';

    protected static ?string $modelLabel = 'parroquia';

    protected static ?string $pluralModelLabel = 'parroquias';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('municipio_id')
                ->label('Municipio')
                ->relationship('municipio', 'nombre')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('municipio.nombre')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Parroquia')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('municipio_id')
                    ->label('Municipio')
                    ->options(fn (): array => Municipio::query()->orderBy('nombre')->pluck('nombre', 'id')->all()),
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
            'index' => Pages\ListParroquias::route('/'),
            'create' => Pages\CreateParroquia::route('/create'),
            'edit' => Pages\EditParroquia::route('/{record}/edit'),
        ];
    }
}
