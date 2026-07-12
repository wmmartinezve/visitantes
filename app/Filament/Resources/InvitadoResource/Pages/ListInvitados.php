<?php

namespace App\Filament\Resources\InvitadoResource\Pages;

use App\Filament\Pages\RegistrarNucleoFamiliar;
use App\Filament\Resources\InvitadoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvitados extends ListRecords
{
    protected static string $resource = InvitadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registrarNucleo')
                ->label('Registrar núcleo familiar')
                ->icon('heroicon-o-user-plus')
                ->url(RegistrarNucleoFamiliar::getUrl())
                ->color('primary'),
            Actions\CreateAction::make()
                ->label('Agregar Invitado'),
        ];
    }
}
