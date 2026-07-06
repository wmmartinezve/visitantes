<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobileUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        $user = $request->user();

        if ($user->isAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['Los administradores usan el panel web en /admin.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'flutter-mobile')->plainTextToken;

        $user->load(['refugio', 'centroAcopio']);

        return response()->json([
            'token' => $token,
            'user' => new MobileUserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): MobileUserResource
    {
        $user = $request->user();
        $user->load(['refugio', 'centroAcopio']);

        return new MobileUserResource($user);
    }
}
