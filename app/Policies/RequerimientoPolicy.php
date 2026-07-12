<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RequerimientoEstatus;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Models\User;

class RequerimientoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAnfitrion() || $user->isCentroAcopio();
    }

    public function view(User $user, Requerimiento $requerimiento): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAnfitrion()) {
            return $requerimiento->invitado !== null
                && $user->hogar_solidario_id === $requerimiento->invitado->hogar_solidario_id;
        }

        if ($user->isCentroAcopio()) {
            return $requerimiento->centro_acopio_id === $user->centro_acopio_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAnfitrion();
    }

    public function createForInvitado(User $user, Invitado $invitado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAnfitrion()
            && $user->hogar_solidario_id !== null
            && $user->hogar_solidario_id === $invitado->hogar_solidario_id;
    }

    public function assign(User $user, Requerimiento $requerimiento): bool
    {
        return $user->isAdmin()
            && $requerimiento->estatus === RequerimientoEstatus::Pendiente;
    }

    public function entregar(User $user, Requerimiento $requerimiento): bool
    {
        if (! $user->isCentroAcopio() || $user->centro_acopio_id === null) {
            return false;
        }

        return $requerimiento->estatus === RequerimientoEstatus::Asignado
            && $requerimiento->centro_acopio_id === $user->centro_acopio_id;
    }
}
