<?php

namespace App\Providers;

use App\Models\Invitado;
use App\Models\Inventario;
use App\Models\Requerimiento;
use App\Policies\InvitadoPolicy;
use App\Policies\InventarioPolicy;
use App\Policies\RequerimientoPolicy;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Invitado::class, InvitadoPolicy::class);
        Gate::policy(Requerimiento::class, RequerimientoPolicy::class);
        Gate::policy(Inventario::class, InventarioPolicy::class);
    }
}
