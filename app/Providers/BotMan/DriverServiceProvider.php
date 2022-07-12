<?php

namespace App\Providers\BotMan;

use App\Drivers\AfricasTalkingDriver;
use App\Drivers\WhatsAppDriver;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Studio\Providers\DriverServiceProvider as ServiceProvider;

class DriverServiceProvider extends ServiceProvider
{
    /**
     * The drivers that should be loaded to
     * use with BotMan.
     *
     * @var array
     */
    protected $drivers = [
        AfricasTalkingDriver::class,
        WhatsAppDriver::class
    ];

    /**
     * @return void
     */
    public function boot()
    {
        parent::boot();

        foreach ($this->drivers as $driver) {
            DriverManager::loadDriver($driver);
        }
    }
}
