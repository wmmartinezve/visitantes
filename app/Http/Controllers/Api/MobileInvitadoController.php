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
            ->with(['jefeFamilia'])
            ->where('refugio_id', $user->refugio_id);

        if ($busqueda !== '') {
            $term = '%'.$busqueda.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('nombre', 'like', $term)
                    ->orWhere('apellido', 'like', $term)
                    ->orWhere('cedula', 'like', $term)
                    ->orWhere('parentesco', 'like', $term);
            });
        }

        return MobileInvitadoResource::collection(
            $query
                ->orderByRaw('COALESCE(jefe_familia_id, id)')
                ->orderByRaw('CASE WHEN jefe_familia_id IS NULL THEN 0 ELSE 1 END')
                ->latest('id')
                ->limit(200)
                ->get(),
        );
    }

    public function show(Request $request, Invitado $invitado): MobileInvitadoResource
    {
        $this->authorize('view', $invitado);

        $invitado->load(['miembrosFamilia', 'requerimientos.centroAcopio']);

        return new MobileInvitadoResource($invitado);
    }
}
