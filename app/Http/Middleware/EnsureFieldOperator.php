<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFieldOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if (! in_array($user->rol, [UserRole::Anfitrion, UserRole::CentroAcopio], true)) {
            abort(403, 'Acceso solo para operadores de campo.');
        }

        return $next($request);
    }
}
