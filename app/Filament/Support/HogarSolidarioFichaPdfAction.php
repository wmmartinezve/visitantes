<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\HogarSolidario;
use App\Models\Invitado;
use Filament\Actions;
use Filament\Tables;

final class HogarSolidarioFichaPdfAction
{
    public static function route(HogarSolidario|Invitado|int $record): string
    {
        $hogarId = match (true) {
            $record instanceof HogarSolidario => $record->getKey(),
            $record instanceof Invitado => (int) $record->hogar_solidario_id,
            default => $record,
        };

        return route('filament.admin.hogares-solidarios.export-pdf', ['hogarSolidario' => $hogarId]);
    }

    public static function makeHeaderAction(callable $recordResolver): Actions\Action
    {
        return Actions\Action::make('exportFichaPdf')
            ->label('Exportar ficha PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->url(fn (): string => self::route($recordResolver()))
            ->openUrlInNewTab();
    }

    public static function makeTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('exportFichaPdf')
            ->label('Ficha PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->url(fn (HogarSolidario $record): string => self::route($record))
            ->openUrlInNewTab();
    }

    public static function makeInvitadoTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('exportFichaPdf')
            ->label('Ficha PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->visible(fn (Invitado $record): bool => $record->esJefeDeFamilia() && $record->hogar_solidario_id !== null)
            ->url(fn (Invitado $record): string => self::route($record))
            ->openUrlInNewTab();
    }
}
