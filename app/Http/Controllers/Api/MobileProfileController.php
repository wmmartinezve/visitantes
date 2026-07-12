<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobileUserResource;
use App\Enums\ActivityAction;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MobileProfileController extends Controller
{
    public function update(Request $request): MobileUserResource
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $before = app(ActivityLogService::class)->snapshot($user);
        $user->update($data);
        $user->refresh();

        $diff = app(ActivityLogService::class)->diff($before, app(ActivityLogService::class)->snapshot($user));
        app(ActivityLogService::class)->log(
            ActivityAction::ProfileUpdated,
            $user,
            'Perfil actualizado (app móvil)',
            $diff,
        );

        $user->load(['refugio', 'centroAcopio']);

        return new MobileUserResource($user);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $user->tokens()->delete();

        app(ActivityLogService::class)->log(
            ActivityAction::PasswordChanged,
            $user,
            'Contraseña actualizada (app móvil)',
        );

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}
