<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvitadoResource\RelationManagers;

use App\Enums\InvitadoEstatus;
use App\Models\Invitado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MiembrosFamiliaRelationManager extends RelationManager
{
    protected static string $relationship = 'miembrosFamilia';

    protected static ?string $title = 'Núcleo familiar';

    protected static ?string $modelLabel = 'familiar';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var Invitado $ownerRecord */
        return $ownerRecord->esJefeDeFamilia();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('parentesco')
                ->label('Parentesco')
                ->options(array_combine(config('visitantes.parentescos'), config('visitantes.parentescos')))
                ->required(),
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre')
                ->required(),
            Forms\Components\TextInput::make('apellido')
                ->label('Apellido')
                ->required(),
            Forms\Components\TextInput::make('cedula')
                ->label('Cédula'),
            Forms\Components\DatePicker::make('fecha_nacimiento')
                ->label('Fecha de nacimiento')
                ->required(),
            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono'),
            Forms\Components\Select::make('estatus')
                ->label('Estatus')
                ->options(collect(InvitadoEstatus::cases())->mapWithKeys(
                    fn (InvitadoEstatus $estatus): array => [$estatus->value => $estatus->label()]
                ))
                ->default(InvitadoEstatus::Activo->value)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parentesco')
                    ->label('Parentesco'),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('apellido')
                    ->label('Apellido'),
                Tables\Columns\TextColumn::make('cedula')
                    ->label('Cédula'),
                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->formatStateUsing(fn (?InvitadoEstatus $state): string => $state?->label() ?? '—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Invitado $owner */
                        $owner = $this->getOwnerRecord();
                        $data['hogar_solidario_id'] = $owner->hogar_solidario_id;
                        $data['jefe_familia_id'] = $owner->id;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
