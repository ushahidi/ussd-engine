<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ushahidi\Platform\Client;

class PlatformSDKProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Client::class, function () {
            $options = [
                'timeout' => (float) env('USHAHIDI_PLATFORM_API_TIMEOUT') ?: 2.0,
            ];

            return new Client(env('USHAHIDI_PLATFORM_API_URL'), $options, env('USHAHIDI_PLATFORM_API_VERSION', 5));
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
