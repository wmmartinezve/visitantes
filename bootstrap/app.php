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
        //
    })->create();
