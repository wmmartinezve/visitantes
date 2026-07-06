<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobileInvitadoResource;
use App\Models\Invitado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MobileInvitadoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invitado::class);

        $user = $request->user();
        $busqueda = trim((string) $request->query('q', ''));

        $query = Invitado::query()
            ->with(['miembrosFamilia'])
            ->where('refugio_id', $user->refugio_id)
            ->whereNull('jefe_familia_id');

        if ($busqueda !== '') {
            $term = '%'.$busqueda.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('nombre', 'like', $term)
                    ->orWhere('apellido', 'like', $term)
                    ->orWhere('cedula', 'like', $term);
            });
        }

        return MobileInvitadoResource::collection(
            $query->latest()->limit(100)->get(),
        );
    }

    public function show(Request $request, Invitado $invitado): MobileInvitadoResource
    {
        $this->authorize('view', $invitado);

        $invitado->load(['miembrosFamilia', 'requerimientos.centroAcopio']);

        return new MobileInvitadoResource($invitado);
    }
}
