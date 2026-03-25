<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider; // ServiceProvider padrão
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Apple\AppleExtendSocialite;

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
        // No Laravel 11, registramos o listener do Socialite assim:
        Event::listen(
            SocialiteWasCalled::class,
            [AppleExtendSocialite::class, 'handle']
        );
    }
}