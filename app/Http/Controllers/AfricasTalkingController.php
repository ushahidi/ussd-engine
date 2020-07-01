<?php

namespace App\Http\Controllers;

use App\Conversations\SurveyConversation;
use Illuminate\Http\Request;

class AfricasTalkingController extends Controller
{
    public function handle()
    {
        $botman = app('botman');

        $botman->fallback(function ($bot) {
            $bot->startConversation(new SurveyConversation());
        });

        try {
            $botman->listen();
        } catch (\Exception $e) {
            $botman->reply('Something went wrong.');
        }
    }
}
