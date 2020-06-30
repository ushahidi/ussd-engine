<?php

namespace App\Drivers;

use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Users\User;
use BotMan\Drivers\Web\WebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AfricasTalkingDriver extends WebDriver
{
    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $data = $request->request->all();

        $payload = [
            'driver' => 'web',
            'message' => isset($data['text']) ? $data['text'] : null,
            'userId' =>  isset($data['sessionId']) ? $data['sessionId'] : null,
        ];

        $this->payload = $payload;
        $this->event = Collection::make(array_merge($data, $payload));
        $this->files = Collection::make($request->files->all());
        $this->config = Collection::make($this->config->get('web', []));
    }

    /**
     * Send out message response.
     */
    public function messagesHandled()
    {
        $messages = $this->buildReply($this->replies);

        // Reset replies
        $this->replies = [];

        $response = 'CON ';

        foreach ($messages as $message) {
            if ($message['type'] === 'text') {
                $response .= $message['text']."\n ";
            }

            if ($message['type'] === 'actions') {
                $response .= $message['text']."\n ";
                foreach ($message['actions'] as $action) {
                    $response .= "Send {$action['value']} for {$action['text']}.\n ";
                }
            }
        }

        Response::create($response, $this->replyStatusCode, ['Content-Type' => 'text/plain'])->send();
    }

    public function matchesRequest()
    {
        $africasTalkingKeys = ['sessionId', 'phoneNumber', 'networkCode', 'serviceCode', 'text'];

        foreach ($africasTalkingKeys as $key) {
            if (is_null($this->event->get($key))) {
                return false;
            }
        }

        return true;
    }
}
