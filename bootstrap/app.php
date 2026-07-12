<?php

use App\Support\InvitadoFotoStorage;
use App\Support\StorageErrorMessage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(fn () => url('/'));

        $middleware->alias([
            'anfitrion' => \App\Http\Middleware\EnsureAnfitrion::class,
            'anfitrion_hogar' => \App\Http\Middleware\EnsureAnfitrionHogar::class,
            'centro_acopio' => \App\Http\Middleware\EnsureCentroAcopio::class,
            'field_operator' => \App\Http\Middleware\EnsureFieldOperator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Throwable $e, $request) {
            if (! $request->is('api/mobile/*')) {
                return null;
            }

            if (! StorageErrorMessage::isStorageFailure($e)) {
                return null;
            }

            return response()->json([
                'message' => StorageErrorMessage::for($e),
            ], 503);
        });
    })->create();
