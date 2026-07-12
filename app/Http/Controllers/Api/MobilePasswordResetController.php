<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Concerns\ThrottlesPasswordReset;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class MobilePasswordResetController extends Controller
{
    use ThrottlesPasswordReset;

    public function sendResetLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->ensurePasswordResetNotRateLimited($data['email']);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user !== null && $this->canResetPassword($user)) {
            try {
                Password::sendResetLink(['email' => $data['email']]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->hitPasswordResetRateLimiter($data['email']);

        return response()->json([
            'message' => 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $this->ensurePasswordResetNotRateLimited($data['email']);

        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                if (! $this->canResetPassword($user)) {
                    return;
                }

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->hitPasswordResetRateLimiter($data['email']);

            return response()->json([
                'message' => __($status),
            ], 422);
        }

        $this->hitPasswordResetRateLimiter($data['email']);

        return response()->json([
            'message' => 'Contraseña restablecida correctamente.',
        ]);
    }

    private function canResetPassword(User $user): bool
    {
        return $user->isAnfitrion() || $user->isCentroAcopio();
    }
}
