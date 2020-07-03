<?php

namespace App\Drivers;

use App\Messages\Outgoing\EndingMessage;
use BotMan\BotMan\Interfaces\WebAccess;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Web\WebDriver;
use Illuminate\Support\Collection;
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
            'message' => isset($data['text']) ? $this->splitMessage($data['text']) : null,
            'userId' =>  isset($data['sessionId']) ? $data['sessionId'] : null,
        ];

        $this->payload = $payload;
        $this->event = Collection::make(array_merge($data, $payload));
        $this->files = Collection::make($request->files->all());
        $this->config = Collection::make($this->config->get('web', []));
    }

    /**
     * @param string|Question|OutgoingMessage $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        if (! $message instanceof WebAccess && ! $message instanceof OutgoingMessage) {
            $this->errorMessage = 'Unsupported message type.';
            $this->replyStatusCode = 500;
        }

        return $message;
    }

    /**
     * @param $messages
     * @return array
     */
    protected function buildReply($messages)
    {
        $sessionIsCompleted = false;
        $replies = [];

        foreach ($messages as $message) {
            if ($message instanceof WebAccess) {
                $messageData = $message->toWebDriver();
                if ($messageData['type'] === 'text') {
                    $replies[] = $messageData['text'];
                } elseif ($messageData['type'] === 'actions') {
                    $replies[] = $messageData['text'];
                    foreach ($messageData['actions'] as $action) {
                        $replies[] = "Send {$action['value']} for {$action['text']}.";
                    }
                }
            } elseif ($message instanceof OutgoingMessage) {
                $replies[] = $message->getText();
            }

            if ($message instanceof EndingMessage) {
                $sessionIsCompleted = true;
            }
        }

        $reply = $sessionIsCompleted ? 'END ' : 'CON ';

        $reply .= implode("\n", $replies);

        return $reply;
    }

    /**
     * Send out message response.
     */
    public function messagesHandled()
    {
        $response = $this->buildReply($this->replies);

        // Reset replies
        $this->replies = [];

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

    private function splitMessage(string $message)
    {
        $parts = explode('*', $message);

        return end($parts);
    }
}
