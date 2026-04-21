<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\SteamExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(SocialiteWasCalled::class, [
            DiscordExtendSocialite::class,
            'handle',
        ]);

        $events->listen(SocialiteWasCalled::class, [
            SteamExtendSocialite::class,
            'handle',
        ]);
    }
}
