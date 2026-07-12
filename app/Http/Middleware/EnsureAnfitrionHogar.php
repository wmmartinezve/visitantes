<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnfitrionHogar
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('anfitrion.login');
        }

        if ($user->rol !== UserRole::Anfitrion) {
            abort(403, 'Acceso restringido a anfitriones.');
        }

        if ($user->hogar_solidario_id === null) {
            return redirect()
                ->route('anfitrion.registrar')
                ->with('info', 'Complete el registro de su hogar solidario y núcleo familiar para continuar.');
        }

        return $next($request);
    }
}
