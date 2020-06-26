<?php

use App\Drivers\USSDDriver;
use App\Http\Conversations\OnboardingConversation;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;

$config = [
  'web' => [
    'matchingData' => [
      'driver' => 'web',
    ],
  ],
  'telegram' => [
    'token' => '1156148638:AAHb6ElYV9HZ2IxvkFuYUur8uMTHl97G5xY',
  ],
];

DriverManager::loadDriver(USSDDriver::class);
DriverManager::loadDriver(TelegramDriver::class);

$botman = BotManFactory::create($config, new LaravelCache());

$botman->fallback(function ($bot) {
    $bot->startConversation(new OnboardingConversation);
});

$botman->listen();
