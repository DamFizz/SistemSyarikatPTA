<?php

namespace App\Providers;

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
        // Railway (and most PaaS platforms) terminate HTTPS at their edge and
        // forward requests to the app over plain HTTP, so Laravel would
        // otherwise generate http:// asset/URL links behind the scenes.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
