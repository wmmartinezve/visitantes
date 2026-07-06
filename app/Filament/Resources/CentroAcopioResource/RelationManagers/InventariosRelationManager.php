<?php

declare(strict_types=1);

namespace App\Filament\Resources\CentroAcopioResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InventariosRelationManager extends RelationManager
{
    protected static string $relationship = 'inventarios';

    protected static ?string $title = 'Inventario';

    protected static ?string $modelLabel = 'ítem';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('item_nombre')
                ->label('Ítem')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('cantidad')
                ->label('Cantidad')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(0),
            Forms\Components\TextInput::make('unidad_medida')
                ->label('Unidad de medida')
                ->required()
                ->maxLength(50)
                ->placeholder('unidades, kg, litros...'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_nombre')
                    ->label('Ítem')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
