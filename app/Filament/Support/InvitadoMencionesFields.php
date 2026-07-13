<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\InvitadoMencionesCatalog;
use Filament\Forms;

final class InvitadoMencionesFields
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function section(): array
    {
        return [
            Forms\Components\Section::make('Menciones opcionales')
                ->description('Solo referencia informativa: marque lo que aplique al Invitado. No reemplaza requerimientos ni trámites formales.')
                ->schema([
                    Forms\Components\CheckboxList::make('menciones_ayudas')
                        ->label('Ayudas recibidas')
                        ->options(InvitadoMencionesCatalog::opciones(InvitadoMencionesCatalog::CATEGORIA_AYUDAS))
                        ->columns(2)
                        ->dehydrateStateUsing(
                            fn (?array $state): ?array => InvitadoMencionesCatalog::normalizeKeys(
                                $state,
                                InvitadoMencionesCatalog::CATEGORIA_AYUDAS,
                            ),
                        ),
                    Forms\Components\CheckboxList::make('menciones_salud')
                        ->label('Atenciones de salud')
                        ->options(InvitadoMencionesCatalog::opciones(InvitadoMencionesCatalog::CATEGORIA_SALUD))
                        ->columns(2)
                        ->dehydrateStateUsing(
                            fn (?array $state): ?array => InvitadoMencionesCatalog::normalizeKeys(
                                $state,
                                InvitadoMencionesCatalog::CATEGORIA_SALUD,
                            ),
                        ),
                    Forms\Components\CheckboxList::make('menciones_tramites')
                        ->label('Trámites de documentos')
                        ->options(InvitadoMencionesCatalog::opciones(InvitadoMencionesCatalog::CATEGORIA_TRAMITES))
                        ->columns(2)
                        ->dehydrateStateUsing(
                            fn (?array $state): ?array => InvitadoMencionesCatalog::normalizeKeys(
                                $state,
                                InvitadoMencionesCatalog::CATEGORIA_TRAMITES,
                            ),
                        ),
                    Forms\Components\Textarea::make('menciones_nota')
                        ->label('Nota breve (opcional)')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeNormalized(array $data): array
    {
        return array_merge($data, InvitadoMencionesCatalog::normalizePayload($data));
    }
}
