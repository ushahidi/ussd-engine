<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;

class Location extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'location';
    }

    public function getRules(): array
    {
        $attributeName = $this->getAttributeName();

        $locationRules = [
            $this->field['required'] ? 'required' : 'nullable',
            'array',
        ];

        $rules = [
            $attributeName  => $locationRules,
            $attributeName.'.latitude' => [
                'required_with:'.$attributeName,
                'numeric',
                'between:-90,90',
            ],
            $attributeName.'.longitude' => [
                'required_with:'.$attributeName,
                'numeric',
                'between:-180,180',
            ],
        ];

        return $rules;
    }

    public function getValueFromAnswer(Answer $answer)
    {
        $text = trim($answer->getText());

        return  $text ? $this->getCoordinatesFromText($text) : null;
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

    public function getValidatedAnswerValue()
    {
        $location = null;

        if ($this->answerValue) {
            $location = [
                'lat' => $this->answerValue['latitude'],
                'lon' => $this->answerValue['longitude'],
            ];
        }

        return $location;
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
}
