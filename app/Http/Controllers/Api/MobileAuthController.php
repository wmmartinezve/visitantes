<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Concerns\ThrottlesAuthentication;
use App\Http\Controllers\Controller;
use App\Http\Resources\MobileUserResource;
use App\Services\AnfitrionMobileProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    use ThrottlesAuthentication;

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $this->ensureIsNotRateLimited($credentials['email']);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            $this->hitRateLimiter($credentials['email']);

            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        $this->clearRateLimiter($credentials['email']);

        $user = $request->user();

        if ($user->isAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['Los administradores usan el panel web en /admin.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'flutter-mobile')->plainTextToken;

        $user = app(AnfitrionMobileProfileService::class)->normalize($user);
        $user->load(['hogarSolidario', 'centroAcopio']);

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
        $user = app(AnfitrionMobileProfileService::class)->normalize($request->user());
        $user->load(['hogarSolidario', 'centroAcopio']);

        return new MobileUserResource($user);
    }
}
