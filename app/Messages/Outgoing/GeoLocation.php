<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\FieldQuestion;
use App\Messages\Outgoing\TextQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Ushahidi\Platform\Client;

class GeoLocation extends TextQuestion
{
    protected $geoLocationResults = [];

    public function getAttributeName(): string
    {
        return 'location';
    }

    public function shouldShowHintsByDefault(): bool
    {
        return true;
    }

    /**
     * Used to know if this question has hints to show.
     *
     * @return bool
     */
    public function hasHints(): bool
    {
        return true;
    }

    /**
     * Return the hints to show for this field.
     *
     * @return string
     */
    public function getHints(): string
    {
        return __('conversation.hints.location');
    }

    public function setAnswer(Answer $answer)
    {
        $this->answerValue = $this->validate($answer);
        if ($this->answerValue) {
            $geoLocationResults = $this->queryLocation($this->answerValue);
            if (empty($geoLocationResults)) {
                throw ValidationException::withMessages([$this->getAttributeName() => __('conversation.geolocation.noResults')]);
            }
            $this->geoLocationResults = $geoLocationResults;
        }
    }

    public function shouldBeSentToPlaform(): bool
    {
        return false;
    }

    public function createsNewQuestion(): bool
    {
        return ! empty($this->geoLocationResults);
    }

    public function queryLocation(string $query): array
    {
        try {
            $sdk = resolve(Client::class);
            $response = $sdk->queryLocation($query, App::getLocale());

            if (isset($response['body'])) {
                return $response['body'];
            }

            throw new Exception('No response body');
        } catch (\Throwable $ex) {
            Log::error("Couldn't fetch available surveys: ".$ex->getMessage());
            throw $ex;
        }
    }

    public function getNextQuestion(): FieldQuestion
    {
        if (count($this->geoLocationResults) == 1) {
            return new AddressQuestion($this->field, $this->geoLocationResults[0]['items']);
        }

        return new LocationGroupQuestion($this->field, $this->geoLocationResults);
    }
}
