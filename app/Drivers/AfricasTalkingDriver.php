<?php

namespace App\Drivers;

use App\Messages\Outgoing\LastScreen;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Web\WebDriver;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Driver to handle incoming requests from Africa's Talking
 * USSD gateway.
 */
class AfricasTalkingDriver extends WebDriver
{
    /**
     * Build payload from incoming request.
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $data = $request->request->all();

        $payload = [
            'driver' => 'web',
            'message' => isset($data['text']) ? $this->splitMessage($data['text']) : null,
            'userId' =>  isset($data['phoneNumber']) ? $data['phoneNumber'] : null,
        ];

        $this->payload = $payload;
        $this->event = Collection::make(array_merge($data, $payload));
        $this->files = Collection::make($request->files->all());
        $this->config = Collection::make($this->config->get('web', []));
    }

    /**
     * Take outgoing messages from Botman and build payload for the service.
     *
     * @param string|Question|OutgoingMessage $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     *
     * @return string|Question|OutgoingMessage
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        if (! $message instanceof Question && ! $message instanceof OutgoingMessage) {
            $this->errorMessage = 'Unsupported message type.';
            $this->replyStatusCode = 500;
        }

        return $message;
    }

    /**
     * Take all the outgoing messages and build a reply understandable by
     * Africa's Talking USSD gateway.
     * @param array $messages
     * @return string|null
     */
    protected function buildReply($messages)
    {
        if (empty($messages)) {
            return;
        }

        $sessionIsCompleted = false;
        $replies = [];

        foreach ($messages as $message) {
            if ($message instanceof OutgoingMessage || $message instanceof Question) {
                $replies[] = $message->getText();
            }

            if ($message instanceof LastScreen && ! $message->getCurrentPage()->hasNext()) {
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

        if ($response) {
            // Reset replies
            $this->replies = [];

            Response::create($response, $this->replyStatusCode, ['Content-Type' => 'text/plain'])->send();
        } else {
            Response::create(null, Response::HTTP_CONFLICT)->send();
        }
    }

    /**
     * Returns true if incoming request matches the expected by this driver.
     *
     * @return bool
     */
    public function matchesRequest(): bool
    {
        $africasTalkingKeys = ['sessionId', 'phoneNumber', 'networkCode', 'serviceCode', 'text'];

        foreach ($africasTalkingKeys as $key) {
            if (is_null($this->event->get($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Split message sent by AF and returns just the last one.
     *
     * @param string $message
     * @return string
     */
    private function splitMessage(string $message): string
    {
        $parts = explode('*', $message);

        return end($parts);
    }
}
