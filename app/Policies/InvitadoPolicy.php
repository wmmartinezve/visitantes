<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invitado;
use App\Models\User;

class InvitadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAnfitrion();
    }

    public function view(User $user, Invitado $invitado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAnfitrion()
            && $user->refugio_id !== null
            && $user->refugio_id === $invitado->refugio_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin()
            || ($user->isAnfitrion() && $user->refugio_id !== null);
    }

    public function createForInvitado(User $user, Invitado $invitado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAnfitrion()
            && $user->refugio_id !== null
            && $user->refugio_id === $invitado->refugio_id;
    }

    public function update(User $user, Invitado $invitado): bool
    {
        return $this->view($user, $invitado);
    }

    public function delete(User $user, Invitado $invitado): bool
    {
        return $user->isAdmin();
    }
}
