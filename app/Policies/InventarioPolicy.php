<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inventario;
use App\Models\User;

class InventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCentroAcopio();
    }

    public function view(User $user, Inventario $inventario): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCentroAcopio()
            && $user->centro_acopio_id === $inventario->centro_acopio_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin()
            || ($user->isCentroAcopio() && $user->centro_acopio_id !== null);
    }

    public function update(User $user, Inventario $inventario): bool
    {
        return $this->view($user, $inventario);
    }

    public function delete(User $user, Inventario $inventario): bool
    {
        return $this->view($user, $inventario);
    }
}
