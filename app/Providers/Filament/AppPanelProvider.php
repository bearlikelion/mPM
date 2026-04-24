<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\EditOrganizationProfile;
use App\Filament\App\Pages\RegisterOrganization;
use App\Http\Middleware\ApplyTenantScopes;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->brandName('mPM')
            ->brandLogo(fn () => Blade::render('<span class="fi-logo"><x-app-logo-icon /><span class="fi-logo-wordmark">mPM</span><span class="gv-panel-tag">org admin</span></span>'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => '<div class="gv-panel-stripe"></div>',
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => '<a href="'.route('dashboard').'" class="gv-back-to-site">back to site</a>',
            )
            ->tenant(Organization::class, slugAttribute: 'slug')
            ->tenantProfile(EditOrganizationProfile::class)
            ->tenantRegistration(RegisterOrganization::class)
            ->tenantMenuItems([
                'register' => fn (Action $action) => $action->label('Create organization'),
            ])
            ->tenantMiddleware([
                ApplyTenantScopes::class,
            ], isPersistent: true)
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
