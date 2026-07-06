<?php

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
            'centro_acopio' => \App\Http\Middleware\EnsureCentroAcopio::class,
            'field_operator' => \App\Http\Middleware\EnsureFieldOperator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Throwable $e, $request) {
            if (! $request->is('api/mobile/*')) {
                return null;
            }

            $message = $e->getMessage();
            $storageFailure = str_contains($message, 'AwsS3V3')
                || str_contains($message, 'Unable to write')
                || str_contains($message, 'Access Denied')
                || str_contains($message, 'AccessDenied')
                || str_contains($message, 'AccessControlListNotSupported')
                || str_contains($message, 'Driver [s3]')
                || $e instanceof \League\Flysystem\FilesystemException;

            if ($storageFailure) {
                return response()->json([
                    'message' => 'No se pudo guardar la foto en el almacenamiento. Contacte al administrador.',
                ], 503);
            }

            return null;
        });
    })->create();
