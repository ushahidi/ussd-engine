<?php

namespace App\Http\Controllers;

use App\Drivers\AfricasTalkingDriver;
use BotMan\BotMan\Drivers\DriverManager;
use Illuminate\Http\Request;

class AfricasTalkingController extends Controller
{
    public function interaction()
    {
        $botman = app('botman');

        try {
            $botman->listen();
        } catch (\Exception $e) {
            $botman->reply('Something went wrong.');
        }
    }
}
