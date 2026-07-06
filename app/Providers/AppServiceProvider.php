<?php

namespace App\Providers;

use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Policies\CentroAcopioPolicy;
use App\Policies\InventarioPolicy;
use App\Policies\InvitadoPolicy;
use App\Policies\RequerimientoPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale(config('app.locale', 'es'));
        Carbon::setLocale(config('app.locale', 'es'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Invitado::class, InvitadoPolicy::class);
        Gate::policy(Requerimiento::class, RequerimientoPolicy::class);
        Gate::policy(Inventario::class, InventarioPolicy::class);
        Gate::policy(CentroAcopio::class, CentroAcopioPolicy::class);
    }
}
