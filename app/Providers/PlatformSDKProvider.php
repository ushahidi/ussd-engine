<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PlatformSDK\Ushahidi;

class PlatformSDKProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Ushahidi::class, function ($app) {
            return new Ushahidi(env('USHAHIDI_PLATFORM_API_URL'), env('USHAHIDI_PLATFORM_API_VERSION', 5));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
