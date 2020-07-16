<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;

class Location extends TextQuestion
{
    public function getRules(): array
    {
        $validationRules = [
          'array',
          function ($attribute, $value, $fail) {
              if (! (isset($value['latitude'])) || ! (isset($value['longitude']))) {
                  return $fail('Location format is not valid.');
              }
              if (! ($this->checkLat($value['latitude']))) {
                  return $fail('Latitude is not valid.');
              }

              if (! ($this->checkLon($value['longitude']))) {
                  return $fail('Longitude is not valid.');
              }
          },
        ];

        if ($this->field['required']) {
            $validationRules[] = 'required';
        }

        $rules = [
            $this->field['key']  => $validationRules,
        ];

        return $rules;
    }

    public function getAnswerBody(Answer $answer): array
    {
        $coordinates = explode(',', $answer->getText());
        $location = [
          'latitude' => isset($coordinates[0]) ? $coordinates[0] : null,
          'longitude' => isset($coordinates[1]) ? $coordinates[1] : null,
        ];

        return [$this->field['key'] => $location];
    }

    private function checkLon($lon)
    {
        if (! is_numeric($lon)) {
            return false;
        }

        if ($lon < -180 || $lon > 180) {
            return false;
        }

        return true;
    }

    private function checkLat($lat)
    {
        if (! is_numeric($lat)) {
            return false;
        }

        if ($lat < -90 || $lat > 90) {
            return false;
        }

        return true;
    }

    public function getAnswerValue()
    {
        $value = null;

        if ($this->answerValue) {
            $value = [
                'lat' => $this->answerValue['latitude'],
                'lon' => $this->answerValue['longitude'],
            ];
        }

        return [
          'value' => $value,
        ];
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
}
