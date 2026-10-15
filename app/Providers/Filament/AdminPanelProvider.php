<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Support\Facades\Blade;
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
            ->brandName(config('app.name', 'Art DB'))
            ->login()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            ->registration(\App\Filament\Pages\Auth\Register::class)
            ->emailVerification(EmailVerificationPrompt::class)
            ->requiresEmailVerification()
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Slate,
                'gray'    => Color::Zinc,
            ])
            ->sidebarCollapsibleOnDesktop()
            // "Support" icon-button in the top bar, just left of the avatar.
            // GLOBAL_SEARCH_AFTER is the slot that sits between global search
            // and the notifications/user-menu cluster, so the button lands
            // right next to the avatar without touching the layout markup.
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => Blade::render('<x-filament.topbar.support-button />'),
            )
            // PWA manifest + apple-touch-icon in the Filament <head>, so an
            // admin installing the app from /admin gets the same install UX
            // as the public layouts.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('<x-pwa-head />'),
            )
            ->navigationGroups([
                'Catalogue',
                'Exhibitions & Movements',
                'CRM',
                'Commerce',
                'System',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
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
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnforceSubscriptionStatus::class,
            ]);
    }
}
