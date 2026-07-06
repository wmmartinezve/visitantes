<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CentroAcopio;
use App\Models\User;

class CentroAcopioPolicy
{
    public function updateGeolocalizacion(User $user, CentroAcopio $centroAcopio): bool
    {
        return $user->isCentroAcopio()
            && $user->centro_acopio_id === $centroAcopio->id
            && $centroAcopio->geolocalizacionEditableDesdeApp();
    }
}
