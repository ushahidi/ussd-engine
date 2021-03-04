<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PlatformGeocodingClient;

class PlatformGeocodingProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(PlatformGeocodingClient::class, function () {
            $timeout = env('USHAHIDI_PLATFORM_GEO_API_TIMEOUT') ?: env('USHAHIDI_PLATFORM_API_TIMEOUT') ?: 2.0;
            $url = env('USHAHIDI_PLATFORM_GEO_API_URL') ?: env('USHAHIDI_PLATFORM_API_URL');
            $api_version = env('USHAHIDI_PLATFORM_GEO_API_VERSION') ?: env('USHAHIDI_PLATFORM_API_VERSION') ?: 5;
            
            $options = [
                'timeout' => $timeout,
            ];

            return new PlatformGeocodingClient($url, $options, $api_version);
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
