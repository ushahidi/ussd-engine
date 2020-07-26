<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;

class Location extends TextQuestion
{
    /**
     * Sets the translated name for this field
     * before parent constructor is executed.
     *
     * @param array $field
     */
    public function __construct(array $field)
    {
        $field['name'] = __('fields.location');
        parent::__construct($field);
    }

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
            $this->name  => $validationRules,
            $this->name.'.latitude' => [
                'required_with:'.$this->field['key'],
                'numeric',
                'between:-90,90',
            ],
            $this->name.'.longitude' => [
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
            $this->name => $text ? $this->getCoordinatesFromText($text) : null,
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
