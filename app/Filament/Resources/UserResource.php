<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\CentroAcopio;
use App\Models\Refugio;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('Correo')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')
                ->label('Contraseña')
                ->password()
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->maxLength(255),
            Forms\Components\Select::make('rol')
                ->label('Rol')
                ->options(collect(UserRole::cases())->mapWithKeys(
                    fn (UserRole $rol): array => [$rol->value => $rol->label()]
                ))
                ->default(UserRole::Admin->value)
                ->required()
                ->live(),
            Forms\Components\Select::make('refugio_id')
                ->label('Refugio asignado')
                ->options(fn (): array => Refugio::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                ->searchable()
                ->visible(fn (Get $get): bool => $get('rol') === UserRole::Anfitrion->value)
                ->required(fn (Get $get): bool => $get('rol') === UserRole::Anfitrion->value),
            Forms\Components\Select::make('centro_acopio_id')
                ->label('Centro de acopio asignado')
                ->options(fn (): array => CentroAcopio::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                ->searchable()
                ->visible(fn (Get $get): bool => $get('rol') === UserRole::CentroAcopio->value)
                ->required(fn (Get $get): bool => $get('rol') === UserRole::CentroAcopio->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rol')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (?UserRole $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('refugio.nombre')
                    ->label('Refugio')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('centroAcopio.nombre')
                    ->label('Centro de acopio')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rol')
                    ->label('Rol')
                    ->options(collect(UserRole::cases())->mapWithKeys(
                        fn (UserRole $rol): array => [$rol->value => $rol->label()]
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
