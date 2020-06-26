<?php

namespace App\Drivers;

use BotMan\Drivers\Web\WebDriver;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class USSDDriver extends WebDriver
{
    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $data = $request->request->all();

        $payload = [
            'driver' => 'web',
            'message' => isset($data['text']) ? $data['text'] : 'Hi',
            'userId' =>  isset($data['sessionId']) ? $data['sessionId'] : null,
        ];

        $this->payload = $payload;
        $this->event = Collection::make($this->payload);
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
        }

        Response::create($response, $this->replyStatusCode, ['Content-Type' => 'text/plain'])->send();
    }
}
