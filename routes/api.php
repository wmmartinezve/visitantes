<?php

use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('api.mobile.')->group(function (): void {
    Route::post('/login', [\App\Http\Controllers\Api\MobileAuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum', 'field_operator'])->group(function (): void {
        Route::post('/logout', [\App\Http\Controllers\Api\MobileAuthController::class, 'logout'])->name('logout');
        Route::get('/me', [\App\Http\Controllers\Api\MobileAuthController::class, 'me'])->name('me');
        Route::get('/catalog', \App\Http\Controllers\Api\OfflineCatalogController::class)->name('catalog');
        Route::post('/sync', \App\Http\Controllers\Api\OfflineSyncController::class)->name('sync');

        Route::get('/invitados', [\App\Http\Controllers\Api\MobileInvitadoController::class, 'index'])->name('invitados.index');
        Route::get('/invitados/{invitado}', [\App\Http\Controllers\Api\MobileInvitadoController::class, 'show'])->name('invitados.show');

        Route::get('/requerimientos', [\App\Http\Controllers\Api\MobileRequerimientoController::class, 'index'])->name('requerimientos.index');
        Route::post('/requerimientos', [\App\Http\Controllers\Api\MobileRequerimientoController::class, 'store'])->name('requerimientos.store');

        Route::get('/entregas', [\App\Http\Controllers\Api\MobileEntregaController::class, 'index'])->name('entregas.index');
        Route::post('/entregas/{requerimiento}/entregar', [\App\Http\Controllers\Api\MobileEntregaController::class, 'entregar'])->name('entregas.entregar');
    });
});
