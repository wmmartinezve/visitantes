<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\OperacionOverviewWidget;
use App\Filament\Widgets\RequerimientosEstatusChartWidget;
use App\Filament\Widgets\RequerimientosFiltradosWidget;
use App\Filament\Widgets\TopRefugiosWidget;
use App\Http\Middleware\PreventAdminSearchIndexing;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">',
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->passwordReset()
            ->profile()
            ->brandName('Visitantes · '.config('visitantes.estado'))
            ->brandLogo(asset(config('visitantes.brand.logo')))
            ->brandLogoHeight('2rem')
            ->favicon(asset(config('visitantes.brand.favicon')))
            ->colors([
                'primary' => Color::hex(config('visitantes.brand.blue')),
                'danger' => Color::hex(config('visitantes.brand.red')),
                'warning' => Color::hex(config('visitantes.brand.yellow')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                OperacionOverviewWidget::class,
                RequerimientosEstatusChartWidget::class,
                RequerimientosFiltradosWidget::class,
                TopRefugiosWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                PreventAdminSearchIndexing::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
