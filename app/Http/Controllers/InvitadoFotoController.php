<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invitado;
use App\Support\InvitadoFotoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvitadoFotoController extends Controller
{
    public function __invoke(Request $request, Invitado $invitado): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            Gate::authorize('view', $invitado);
        }

        abort_unless($invitado->foto_ingreso, 404);

        $filesystem = InvitadoFotoStorage::filesystem($invitado->foto_ingreso);

        abort_unless($filesystem !== null, 404);

        return $filesystem->response($invitado->foto_ingreso);
    }
}
