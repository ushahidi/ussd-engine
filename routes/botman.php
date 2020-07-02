<?php

use App\Conversations\SurveyConversation;
use App\Drivers\AfricasTalkingDriver;
use BotMan\BotMan\Drivers\DriverManager;

DriverManager::loadDriver(AfricasTalkingDriver::class);

$botman = resolve('botman');

$botman->hears('Hi', function ($bot) {
    $bot->reply('Hello!');
});

$botman->hears('survey', function ($bot) {
    $bot->startConversation(new SurveyConversation());
});
