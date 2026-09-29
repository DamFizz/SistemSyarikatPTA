<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Clock-in/out attempts: enough for honest retries, too few for brute-forcing.
        RateLimiter::for('attendance', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // The attendance page polls the office WiFi status every few seconds.
        RateLimiter::for('attendance-status', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
