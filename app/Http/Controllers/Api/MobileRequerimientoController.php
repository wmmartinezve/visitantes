<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\RequerimientoEstatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MobileRequerimientoResource;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Services\ActivityLogService;
use App\Support\InsumoCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MobileRequerimientoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Requerimiento::class);

        $user = $request->user();
        $filtro = (string) $request->query('estatus', 'todos');

        $query = Requerimiento::query()
            ->with(['invitado.refugio', 'centroAcopio'])
            ->latest();

        if ($user->isAnfitrion()) {
            $query->whereHas('invitado', fn ($q) => $q->where('refugio_id', $user->refugio_id));
        } elseif ($user->isCentroAcopio()) {
            $query->where('centro_acopio_id', $user->centro_acopio_id);
        }

        if ($filtro !== 'todos' && in_array($filtro, ['pendiente', 'asignado', 'entregado'], true)) {
            $query->where('estatus', RequerimientoEstatus::from($filtro));
        }

        return MobileRequerimientoResource::collection($query->limit(100)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Requerimiento::class);

        $validated = $request->validate([
            'invitado_id' => ['required', 'integer', 'exists:invitados,id'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'subcategoria' => ['nullable', 'string', 'max:255'],
            'item_solicitado' => ['nullable', 'string', 'max:255'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $insumo = InsumoCatalog::normalizeRequerimiento($validated);

        $invitado = Invitado::query()->findOrFail($validated['invitado_id']);
        $this->authorize('createForInvitado', $invitado);

        $requerimiento = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $request->user()->id,
            'categoria' => $insumo['categoria'],
            'subcategoria' => $insumo['subcategoria'],
            'item_solicitado' => $insumo['item_solicitado'],
            'cantidad' => $validated['cantidad'],
            'estatus' => RequerimientoEstatus::Pendiente,
        ]);

        app(ActivityLogService::class)->created($requerimiento, 'Requerimiento creado (app móvil)');

        $requerimiento->load(['invitado', 'centroAcopio']);

        return response()->json([
            'data' => new MobileRequerimientoResource($requerimiento),
        ], 201);
    }
}
