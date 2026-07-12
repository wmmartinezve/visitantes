<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnfitrion
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('anfitrion.login');
        }

        if ($user->rol !== UserRole::Anfitrion || $user->hogar_solidario_id === null) {
            abort(403, 'Acceso restringido a anfitriones con refugio asignado.');
        }

        return $next($request);
    }
}
