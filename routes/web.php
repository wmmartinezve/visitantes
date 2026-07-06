<?php

use App\Http\Controllers\AcopioLogoutController;
use App\Http\Controllers\AnfitrionLogoutController;
use App\Livewire\Acopio\Dashboard as AcopioDashboard;
use App\Livewire\Acopio\GestionInventario;
use App\Livewire\Acopio\Login as AcopioLogin;
use App\Livewire\Acopio\Requerimientos as AcopioRequerimientos;
use App\Livewire\Anfitrion\Dashboard;
use App\Livewire\Anfitrion\InvitadoDetalle;
use App\Livewire\Anfitrion\ListadoInvitados;
use App\Livewire\Anfitrion\Login;
use App\Livewire\Anfitrion\Requerimientos as AnfitrionRequerimientos;
use App\Livewire\Anfitrion\RegistrarInvitado;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'field_operator'])->prefix('api/offline')->name('api.offline.')->group(function (): void {
    Route::get('/catalog', \App\Http\Controllers\Api\OfflineCatalogController::class)->name('catalog');
    Route::post('/sync', \App\Http\Controllers\Api\OfflineSyncController::class)->name('sync');
});

Route::prefix('anfitrion')->name('anfitrion.')->group(function (): void {
    Route::get('/login', Login::class)->name('login');

    Route::middleware(['auth', 'anfitrion'])->group(function (): void {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/registrar', RegistrarInvitado::class)->name('registrar');
        Route::get('/invitados', ListadoInvitados::class)->name('invitados');
        Route::get('/invitados/{invitado}', InvitadoDetalle::class)->name('invitado');
        Route::get('/requerimientos', AnfitrionRequerimientos::class)->name('requerimientos');
        Route::post('/logout', AnfitrionLogoutController::class)->name('logout');
    });
});

Route::prefix('acopio')->name('acopio.')->group(function (): void {
    Route::get('/login', AcopioLogin::class)->name('login');

    Route::middleware(['auth', 'centro_acopio'])->group(function (): void {
        Route::get('/', AcopioDashboard::class)->name('dashboard');
        Route::get('/inventario', GestionInventario::class)->name('inventario');
        Route::get('/requerimientos', AcopioRequerimientos::class)->name('requerimientos');
        Route::post('/logout', AcopioLogoutController::class)->name('logout');
    });
});
