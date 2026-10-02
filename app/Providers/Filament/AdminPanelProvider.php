<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetAdminLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages;
use Filament\SpatieLaravelTranslatablePlugin;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            ->passwordReset()
            ->profile()
            ->brandName('Heaven Gate')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.4rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                // Copper from the logo, night navy as gray base.
                'primary' => [
                    50 => '#faf5ef', 100 => '#f3e6d6', 200 => '#e8cfb1', 300 => '#dbb489', 400 => '#cc9b6c',
                    500 => '#b8875a', 600 => '#9a6c42', 700 => '#7a5232', 800 => '#5f4029', 900 => '#4b3322', 950 => '#2a1b11',
                ],
                'gray' => Color::Slate,
                'info' => Color::hex('#3d7891'),
                'success' => Color::hex('#4e7d5b'),
                'warning' => Color::hex('#e0813a'),
                'danger' => Color::hex('#b4483c'),
            ])
            ->font('Manrope')
            // Small brand/calendar stylesheet injected without a separate Tailwind v3 theme build.
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.styles'))
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            // Labels are closures so they follow the admin language chosen per request.
            ->navigationGroups([
                NavigationGroup::make(fn () => __('Reservations')),
                NavigationGroup::make(fn () => __('Inventory')),
                NavigationGroup::make(fn () => __('Pricing')),
                NavigationGroup::make(fn () => __('Content')),
                NavigationGroup::make(fn () => __('Settings'))->collapsed(),
            ])
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.locale-switch'))
            ->plugin(SpatieLaravelTranslatablePlugin::make()->defaultLocales(array_keys(config('heavengate.locales'))))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SetAdminLocale::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }

    public function boot(): void
    {
        // Every admin label (explicit or generated from a field name) goes through __(),
        // so lang/ar.json translates the whole panel.
        foreach ([
            \Filament\Forms\Components\Component::class,
            \Filament\Infolists\Components\Component::class,
            \Filament\Tables\Columns\Column::class,
            \Filament\Tables\Filters\BaseFilter::class,
            \Filament\Actions\StaticAction::class,
        ] as $component) {
            $component::configureUsing(fn ($c) => $c->translateLabel());
        }
    }
}
