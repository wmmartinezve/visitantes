<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OfflineCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineCatalogController extends Controller
{
    public function __invoke(Request $request, OfflineCatalogService $catalog): JsonResponse
    {
        return response()->json($catalog->buildFor($request->user()));
    }
}
