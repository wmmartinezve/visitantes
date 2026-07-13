<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobilePasswordResetController;
use App\Http\Controllers\Api\MobileProfileController;
use App\Http\Controllers\InvitadoFotoController;
use App\Http\Controllers\Api\MobileHogarController;
use App\Http\Controllers\Api\MobileInvitadoController;
use App\Http\Controllers\Api\OfflineCatalogController;
use App\Http\Controllers\Api\OfflineSyncController;
use App\Support\VisitantesFeatures;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('api.mobile.')->group(function (): void {
    Route::post('/login', [MobileAuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [MobilePasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::post('/reset-password', [MobilePasswordResetController::class, 'reset'])->name('password.update');

    Route::middleware(['auth:sanctum', 'field_operator'])->group(function (): void {
        Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
        Route::get('/me', [MobileAuthController::class, 'me'])->name('me');
        Route::put('/profile', [MobileProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [MobileProfileController::class, 'updatePassword'])->name('profile.password');
        Route::get('/catalog', OfflineCatalogController::class)->name('catalog');
        Route::post('/sync', OfflineSyncController::class)->name('sync');

        Route::get('/hogares', [MobileHogarController::class, 'index'])->name('hogares.index');
        Route::get('/hogares/{hogarSolidario}', [MobileHogarController::class, 'show'])->name('hogares.show');
        Route::put('/hogar-activo', [MobileHogarController::class, 'activar'])->name('hogar.activo');

        Route::get('/invitados', [MobileInvitadoController::class, 'index'])->name('invitados.index');
        Route::post('/invitados', [MobileInvitadoController::class, 'store'])->name('invitados.store');
        Route::get('/invitados/{invitado}', [MobileInvitadoController::class, 'show'])->name('invitados.show');
        Route::put('/invitados/{invitado}/menciones', [MobileInvitadoController::class, 'updateMenciones'])->name('invitados.menciones');
        Route::post('/invitados/{invitado}/foto', [MobileInvitadoController::class, 'updateFoto'])->name('invitados.foto.store');
        Route::get('/invitados/{invitado}/foto', InvitadoFotoController::class)->name('invitados.foto');

        if (VisitantesFeatures::logistica()) {
            Route::get('/requerimientos', [\App\Http\Controllers\Api\MobileRequerimientoController::class, 'index'])->name('requerimientos.index');
            Route::post('/requerimientos', [\App\Http\Controllers\Api\MobileRequerimientoController::class, 'store'])->name('requerimientos.store');

            Route::get('/entregas', [\App\Http\Controllers\Api\MobileEntregaController::class, 'index'])->name('entregas.index');
            Route::post('/entregas/{requerimiento}/entregar', [\App\Http\Controllers\Api\MobileEntregaController::class, 'entregar'])->name('entregas.entregar');

            Route::get('/centro', [\App\Http\Controllers\Api\MobileCentroAcopioController::class, 'show'])->name('centro.show');
            Route::put('/centro/geolocalizacion', [\App\Http\Controllers\Api\MobileCentroAcopioController::class, 'updateGeolocalizacion'])->name('centro.geolocalizacion');
        }
    });
});
