<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\SelectQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;

class LocationGroupQuestion extends SelectQuestion
{
    public const NOT_LISTED = '-';

    /**
     * Construct a language selection question with the provided languages list.
     *
     * @param array $availableLanguages
     */
    public function __construct(array $field, array $groups)
    {
        $groups = array_map(function ($group) {
            return [
                'display' => $group['groupName'],
                'value' => $group['items'],
            ];
        }, $groups);

        $groups[] = [
            'display' => __('conversation.geolocation.searchAgain'),
            'value' => self::NOT_LISTED,
        ];

        $field['options'] = $groups;

        parent::__construct($field, 'value', 'display');
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
        return __('conversation.hints.locationGroup');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName(): string
    {
        return 'country';
    }

    public function createsNewQuestion(): bool
    {
        return true;
    }

    public function shouldBeSentToPlaform(): bool
    {
        return false;
    }

    public function getNextQuestion(): FieldQuestion
    {
        unset($this->field['options']);
        $answerValue = $this->getValidatedAnswerValue();
        if ($answerValue == self::NOT_LISTED) {
            return new GeoLocation($this->field);
        }

        return new AddressQuestion($this->field, $answerValue);
    }
}
