<?php

namespace App\Http\Controllers;

use App\Conversations\SurveyConversation;
use BotMan\BotMan\BotMan;
use Illuminate\Support\Facades\Log;

/**
 * This controller allows to interact with Botman for testing purposes.
 */
class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        Log::debug('BotManController.handle');

        $botman = app('botman');

        /* Testing greeting reactions */
        $botman->hears('hear hear', function ($bot) {
            Log::debug('BotManController.handle: hear hear fired');
            $bot->reply('Orrrderrrr!');
        });

        /* Defaults to kick off the survey conversation */
        $botman->fallback(function ($bot) {
            Log::debug('BotManController.handle: fallback fired');
            $this->startConversation($bot);
        });

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    /**
     * Loaded through routes/botman.php.
     * @param  BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new SurveyConversation());
    }
}
