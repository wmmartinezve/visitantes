<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return 'Operación — '.config('visitantes.estado');
    }

    public function getSubheading(): ?string
    {
        return 'Gestión de Invitados, refugios y logística de insumos en el estado '
            .config('visitantes.estado').', '.config('visitantes.pais').'.';
    }
}
