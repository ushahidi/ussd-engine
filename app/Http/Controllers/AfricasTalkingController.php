<?php

namespace App\Http\Controllers;

use App\Conversations\SurveyConversation;
use Illuminate\Support\Facades\Log;

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
            Log::error($e->getMessage());
            $botman->reply('Something went wrong.');
        }
    }
}
