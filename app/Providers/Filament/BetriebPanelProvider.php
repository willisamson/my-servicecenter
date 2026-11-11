<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Das Dashboard der Betriebe: <subdomain>.my-servicecenter.com/dashboard
 * Läuft immer im Kontext eines Betriebs (eigene Datenbank, eigene Benutzer).
 */
class BetriebPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('betrieb')
            ->path('dashboard')
            ->login()
            ->brandName(fn (): string => tenant('name') ?? 'my-servicecenter')
            ->colors([
                'primary' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Betrieb/Resources'), for: 'App\Filament\Betrieb\Resources')
            ->discoverPages(in: app_path('Filament/Betrieb/Pages'), for: 'App\Filament\Betrieb\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Betrieb anhand der Subdomain erkennen – "persistent", damit es auch
            // für die Livewire-Anfragen (Formulare, Login) des Dashboards gilt.
            ->middleware([
                InitializeTenancyByDomainOrSubdomain::class,
                PreventAccessFromCentralDomains::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
