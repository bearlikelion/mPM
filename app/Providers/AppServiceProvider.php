<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, [
            \SocialiteProviders\Discord\DiscordExtendSocialite::class,
            'handle',
        ]);

        $events->listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, [
            \SocialiteProviders\Steam\SteamExtendSocialite::class,
            'handle',
        ]);
    }
}
