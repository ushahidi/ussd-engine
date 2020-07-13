<?php

namespace App\Http\Controllers;

use App\Conversations\SurveyConversation;
use Illuminate\Support\Facades\Log;

class AfricasTalkingController extends Controller
{
    /**
     * Handle each request from Africa's Talking USSD gateway.
     *
     * @return void
     */
    public function handle()
    {
        $botman = app('botman');

        /*
         * If the message sent by the AT gateway didn't match any command
         * or previous conversation, start a new survey conversation.
         */
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
