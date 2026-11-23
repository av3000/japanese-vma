<?php

namespace App\Providers\Filament;

use App\Filament\AdminNavigationGroup;
use App\Filament\Pages\Dashboard;
use App\Filament\Support\PanelActor;
use App\Http\Middleware\EnsureHorizonBasicAuth;
use App\Http\Middleware\SetActivitySource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups(AdminNavigationGroup::class)
            ->navigationItems([
                // Horizon already shows queues, workers and failed jobs; the panel links to it and
                // the panel session is accepted there (EnsureHorizonBasicAuth).
                NavigationItem::make('Horizon')
                    ->url(static fn (): string => url((string) config('horizon.path', 'horizon')), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedQueueList)
                    ->group(AdminNavigationGroup::Operations)
                    ->sort(40)
                    ->visible(static fn (): bool => PanelActor::may(EnsureHorizonBasicAuth::PANEL_PERMISSION)),
                NavigationItem::make('Telescope')
                    ->url(static fn (): string => url((string) config('telescope.path', 'telescope')), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->group(AdminNavigationGroup::Operations)
                    ->sort(50)
                    ->visible(static fn (): bool => (bool) config('telescope.enabled')
                        && PanelActor::may(EnsureHorizonBasicAuth::PANEL_PERMISSION)),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetActivitySource::class.':panel',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
