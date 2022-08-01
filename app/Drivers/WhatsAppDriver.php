<?php

namespace App\Drivers;

use App\Drivers\Traits\DriverClassificationInfo;
use App\Drivers\Exceptions\WhatsAppConnectionException;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\VerifiesService;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * WhatsApp Cloud API driver
 * 
 * This came together by liberally adapting these gists:
 *   - https://gist.github.com/echr/d5141b0a3210462d32ed1fc3a621c371
 *   - https://gist.github.com/hugomartin89/b22f7c8c32be3732993be1027f543ab7
 * 
 */
class WhatsAppDriver extends HttpDriver implements VerifiesService
{
    const DRIVER_NAME = 'WhatsApp';

    use DriverClassificationInfo;

    protected $messageFormat = 'messaging';
    protected $driverProtocol = 'whatsapp';

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request)
    {
        $payload = $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        Log::debug('WhatsAppDriver.buildPayload:', [ 'payload' => $this->payload->all() ]);

        if ($payload->get('object') !== 'whatsapp_business_account'
                || !is_array($payload->get('entry'))) {
            return;
        }
        $entries = $this->entries = $payload->get('entry');

        // Process entries and collect messages
        $this->eventMessages = [];
        foreach($entries as $entry) {
            $entryId = $entry['id'];
            if (!isset($entry['changes']) || !is_array($entry['changes'])) {
                Log::warn('WhatsAppDriver.buildPayload: malformed event notification entry', ['entry' => $entry]);
                continue;
            }
            foreach($entry['changes'] as $change) {
                if ($change['field'] !== 'messages'
                        || !isset($change['value']['messaging_product'])
                        || $change['value']['messaging_product'] !== 'whatsapp') {
                    // We process WhatsApp messages only
                    continue;
                }
                if (!isset($change['value']['messages'])) {
                    continue;
                }
                // Iterate through messages adding metadata and contacts to each
                foreach($change['value']['messages'] as $msg) {
                    $msg['metadata'] = $change['value']['metadata'];
                    $msg['contacts'] = $change['value']['contacts'];
                    $this->eventMessages[] = $msg;
                }
            }
        }

        $this->content = $request->getContent();
        
        $this->config = Collection::make($this->config->get('whatsapp', []));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest(): bool
    {
        // TODO: Validating Payload's signature
        if (isset($this->entries) && !is_null($this->entries)) {
            Log::debug('WhatsAppDriver.matchesRequest: true');
            return true;
        } else {
            Log::debug('WhatsAppDriver.matchesRequest: false');
            return false;
        }
    }

    /**
     * Specific to respond to Facebook's webhook verification request
     * @param  Request  $request
     * @return null|Response
     */
    public function verifyRequest(Request $request)
    {
        $mode = $request->get('hub_mode');    // Laravel changes 'hub.mode' -> 'hub_mode'
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode && $token && $challenge) {
            Log::debug('WhatsAppDriver.verifyRequest: Incoming verification request', [
                'mode' => $mode,
                'token' => $token,
                'challenge' => $challenge,
                'config(verify_token)' => config('whatsapp.cloud_api_webhook_verify_token'),
            ]);
            if ($mode === 'subscribe' && strval($token) === config('whatsapp.cloud_api_webhook_verify_token')) {
                Log::info('WhatsAppDriver.verifyRequest: Verification request passed');
                return response($challenge, 200)->header('Content-Type', 'text/plain')->send();
            }
            Log::warn('WhatsAppDriver.verifyRequest: Verification request failed: bad parameters');
        }
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        // Guard against multiple invocations for same request
        if (empty($this->messages)) {
            foreach ($this->eventMessages as $msg) {
                $this->messages[] =
                    new IncomingMessage(
                        $msg['text']['body'], // message
                        $msg['from'],         // sender
                        $msg['metadata']['display_phone_number'], // recipient
                        $msg,                 // payload
                        null,                 // bot_id
                    );
            }
            Log::debug('WhatsAppDriver.getMessages: ', [
                'messages' => $this->messages
            ]);
        }

        return $this->messages;
    }

    /**
     * Retrieve User information.
     * @param IncomingMessage $matchingMessage
     * @return UserInterface
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $contact = Collection::make($matchingMessage->getPayload()->get('contacts')[0]);
        return new User(
            $contact->get('wa_id'),
            $contact->get('profile')['name'],
            null,
            $contact->get('wa_id'),
            $contact
        );
    }

    /**
     * @param IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * @param string|\BotMan\BotMan\Messages\Outgoing\Question $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        Log::debug('WhatsAppDriver.buildServicePayload', [
            'message' => $message,
            'matchingMessage' => $matchingMessage,
            'additionalParameters' => $additionalParameters,
        ]);

        // Obtain our number id
        $orig_payload = $matchingMessage->getPayload();
        [ 'phone_number_id' => $phone_id ] = $orig_payload['metadata'];

        // For message of type text? 
        $result = [
            'endpoint' => "$phone_id/messages",
            'body' => [
                'messaging_product' => 'whatsapp',
                'to' => $matchingMessage->getSender(),
                'type' => 'text',
                'recipient_type' => 'individual',
                'text' => [
                    'preview_url' => false,
                    'body' => $message->getText(),
                ],
            ]
        ];

        Log::debug('WhatsAppDriver.buildServicePayload result:', [
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        Log::debug('WhatsAppDriver.sendPayload', [
            'payload' => $payload,
        ]);

        if ($this->config->get('throw_http_exceptions')) {
            return $this->postWithExceptionHandling(
                $this->buildApiUrl($payload['endpoint']), 
                [],
                $payload['body'],
                $this->buildAuthHeader(), true);
        }

        $result = $this->http->post(
            $this->buildApiUrl($payload['endpoint']), 
            [], 
            $payload['body'],
            $this->buildAuthHeader(), true);

        Log::debug('WhatsAppDriver.sendPayload result:', [
            'result' => strval($result)
        ]);
        return $result;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        if (!empty($this->config->get('cloud_api_access_token'))) {
            Log::debug('WhatsAppDriver.isConfigured: true');
            return true;
        } else {
            Log::debug('WhatsAppDriver.isConfigured: false');
            return false;
        }
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        Log::debug('WhatsAppDriver.sendRequest', [
            'endpoint' => $endpoint,
            'parameters' => $parameters,
            'matchingMessage' => $matchingMessage,
        ]);

        $parameters = array_replace_recursive([
            'to' => $matchingMessage->getRecipient(),
        ], $parameters);

        if ($this->config->get('throw_http_exceptions')) {
            return $this->postWithExceptionHandling($this->buildApiUrl($endpoint), [], $parameters, $this->buildAuthHeader());
        }

        return $this->http->post($this->buildApiUrl($endpoint), [], $parameters, $this->buildAuthHeader());
    }

    protected function buildApiUrl($endpoint)
    {
        $result = config('whatsapp.cloud_api_base_url') . '/' . $endpoint;
        Log::debug('WhatsAppDriver.buildApiUrl result:', [
            'result' => $result
        ]);
        return $result;
    }

    protected function buildAuthHeader()
    {
        // TODO: Find a way to refresh the access token
        $token = config('whatsapp.cloud_api_access_token');

        $result = [
            "Authorization: Bearer $token",
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        Log::debug('WhatsAppDriver.buildAuthHeader', [
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * @param $url
     * @param array $urlParameters
     * @param array $postParameters
     * @param array $headers
     * @param bool $asJSON
     * @param int $retryCount
     * @return Response
     * @throws \Modules\ChatBot\Drivers\Whatsapp\WhatsappConnectionException
     */
    private function postWithExceptionHandling(
        $url,
        array $urlParameters = [],
        array $postParameters = [],
        array $headers = [],
        $asJSON = false,
        int $retryCount = 0
    ) {
        $response = $this->http->post($url, $urlParameters, $postParameters, $headers, $asJSON);
        $responseData = json_decode($response->getContent(), true);

        if ($response->isSuccessful()) {
            return $responseData;
        }

        $responseData['errors']['code'] = $responseData['errors']['code'] ?? 'No description from Vendor';
        $responseData['errors']['title'] = $responseData['errors']['title'] ?? 'No error code from Vendor';

        $message = "Status Code: {$response->getStatusCode()}\n".
            "Description: ".print_r($responseData['errors']['title'], true)."\n".
            "Error Code: ".print_r($responseData['errors']['code'], true)."\n".
            "URL: $url\n".
            "URL Parameters: ".print_r($urlParameters, true)."\n".
            "Post Parameters: ".print_r($postParameters, true)."\n".
            "Headers: ". print_r($headers, true)."\n";

        throw new WhatsAppConnectionException($message);
    }
}