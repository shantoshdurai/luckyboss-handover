<?php

namespace App\Providers;

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
        // luckyboss.org terminates SSL at a proxy, so PHP sees plain HTTP and
        // generates http:// links — which browsers block as mixed content and
        // which break assets and redirects on the live site. Ported from the
        // live deployment, where this was found the hard way.
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
