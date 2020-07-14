<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;

class Location extends TextQuestion
{
    public function getRules(): array
    {
        $validationRules = [
            'nullable',
            'array',
        ];

        if ($this->field['required']) {
            array_unshift($validationRules, 'required');
        }

        $rules = [
            $this->field['key']  => $validationRules,
            $this->field['key'].'.latitude' => [
                'required_with:'.$this->field['key'],
                'numeric',
                'between:-90,90',
            ],
            $this->field['key'].'.longitude' => [
                'required_with:'.$this->field['key'],
                'numeric',
                'between:-180,180',
            ],
        ];

        return $rules;
    }

    public function getAnswerBody(Answer $answer): array
    {
        $text = trim($answer->getText());
        $body = [
            $this->field['key'] => $text ? $this->getCoordinatesFromText($text) : null,
        ];

        return $body;
    }

    /**
     * Extract latitude and longitude from provided text.
     *
     * @param string $text
     * @return array
     */
    private function getCoordinatesFromText(string $text): array
    {
        $coordinates = explode(',', $text);
        $location = [
            'latitude' => isset($coordinates[0]) ? $coordinates[0] : null,
            'longitude' => isset($coordinates[1]) ? $coordinates[1] : null,
        ];

        return $location;
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
}
