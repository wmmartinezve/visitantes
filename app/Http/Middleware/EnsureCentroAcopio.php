<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentroAcopio
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('acopio.login');
        }

        if ($user->rol !== UserRole::CentroAcopio || $user->centro_acopio_id === null) {
            abort(403, 'Acceso restringido a operadores de centro de acopio.');
        }

        return $next($request);
    }
}
