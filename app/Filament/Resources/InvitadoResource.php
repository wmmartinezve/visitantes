<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\InvitadoEstatus;
use App\Filament\Resources\InvitadoResource\Pages;
use App\Filament\Resources\InvitadoResource\RelationManagers\MiembrosFamiliaRelationManager;
use App\Models\Invitado;
use App\Support\InvitadoFotoStorage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvitadoResource extends Resource
{
    protected static ?string $model = Invitado::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Invitados';

    protected static ?string $modelLabel = 'invitado';

    protected static ?string $pluralModelLabel = 'invitados';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?int $navigationSort = 12;

    public static function getGloballySearchableAttributes(): array
    {
        return ['cedula', 'nombre', 'apellido'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        /** @var Invitado $record */
        return [
            'Refugio' => $record->refugio?->nombre ?? '—',
            'Estatus' => $record->estatus?->label() ?? '—',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificación')
                ->schema([
                    Forms\Components\ViewField::make('foto_preview')
                        ->label('Foto de ingreso')
                        ->view('filament.forms.components.invitado-foto-preview')
                        ->visible(fn (?Invitado $record): bool => $record !== null)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('foto_ingreso')
                        ->label(fn (?Invitado $record): string => $record?->foto_ingreso ? 'Reemplazar foto' : 'Foto de ingreso')
                        ->disk(InvitadoFotoStorage::privateDisk())
                        ->directory(fn (?Invitado $record): string => $record
                            ? 'invitados/fotos/'.$record->id
                            : 'invitados/fotos/pendientes')
                        ->fetchFileInformation(false)
                        ->image()
                        ->maxSize(8192)
                        ->visible(fn (?Invitado $record): bool => $record === null || $record->esJefeDeFamilia())
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('apellido')
                        ->label('Apellido')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('cedula')
                        ->label('Cédula')
                        ->maxLength(20),
                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->required()
                        ->maxDate(now()),
                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\Select::make('estatus')
                        ->label('Estatus')
                        ->options(collect(InvitadoEstatus::cases())->mapWithKeys(
                            fn (InvitadoEstatus $estatus): array => [$estatus->value => $estatus->label()]
                        ))
                        ->default(InvitadoEstatus::Activo->value)
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Ubicación y familia')
                ->schema([
                    Forms\Components\Select::make('refugio_id')
                        ->label('Refugio')
                        ->relationship('refugio', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\Toggle::make('es_jefe_familia')
                        ->label('Es jefe de familia')
                        ->default(true)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Toggle $component, ?Invitado $record): void {
                            $component->state($record === null || $record->jefe_familia_id === null);
                        }),
                    Forms\Components\Select::make('jefe_familia_id')
                        ->label('Jefe de familia')
                        ->options(function (Get $get): array {
                            $refugioId = $get('refugio_id');

                            if (! $refugioId) {
                                return [];
                            }

                            return Invitado::query()
                                ->where('refugio_id', $refugioId)
                                ->whereNull('jefe_familia_id')
                                ->orderBy('apellido')
                                ->get()
                                ->mapWithKeys(fn (Invitado $invitado): array => [
                                    $invitado->id => $invitado->nombreCompleto(),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->visible(fn (Get $get): bool => ! $get('es_jefe_familia'))
                        ->required(fn (Get $get): bool => ! $get('es_jefe_familia')),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto_url')
                    ->label('Foto')
                    ->state(fn (Invitado $record): ?string => $record->fotoUrl())
                    ->checkFileExistence(false)
                    ->url(fn (Invitado $record): ?string => $record->fotoUrl())
                    ->openUrlInNewTab()
                    ->circular()
                    ->defaultImageUrl(fn (): string => 'https://ui-avatars.com/api/?background=002776&color=fff&name=I'),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('apellido')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                Tables\Columns\TextColumn::make('refugio.nombre')
                    ->label('Refugio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->formatStateUsing(fn (?InvitadoEstatus $state): string => $state?->label() ?? '—')
                    ->color(fn (?InvitadoEstatus $state): string => match ($state) {
                        InvitadoEstatus::Activo => 'success',
                        InvitadoEstatus::Egresado => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('miembros_familia_count')
                    ->label('Familiares')
                    ->counts('miembrosFamilia')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('refugio_id')
                    ->label('Refugio')
                    ->relationship('refugio', 'nombre'),
                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options(collect(InvitadoEstatus::cases())->mapWithKeys(
                        fn (InvitadoEstatus $estatus): array => [$estatus->value => $estatus->label()]
                    )),
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
            ->with('jefeFamilia')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MiembrosFamiliaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvitados::route('/'),
            'create' => Pages\CreateInvitado::route('/create'),
            'edit' => Pages\EditInvitado::route('/{record}/edit'),
        ];
    }
}
