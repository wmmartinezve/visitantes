<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invitado;
use App\Support\InvitadoMencionesCatalog;

class InvitadoMencionesService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Invitado $invitado, array $payload): Invitado
    {
        $invitado->update(InvitadoMencionesCatalog::normalizePayload($payload));

        return $invitado->fresh();
    }
}
