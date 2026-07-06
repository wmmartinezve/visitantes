<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function __invoke(Request $request, OfflineSyncService $sync): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.client_id' => ['required', 'string', 'max:64'],
            'items.*.type' => ['required', 'string', 'max:64'],
            'items.*.payload' => ['required', 'array'],
        ]);

        $results = $sync->sync($request->user(), $validated['items']);

        $ok = collect($results)->where('status', 'ok')->count();
        $failed = collect($results)->where('status', 'error')->count();

        return response()->json([
            'results' => $results,
            'summary' => [
                'ok' => $ok,
                'failed' => $failed,
            ],
        ]);
    }
}
