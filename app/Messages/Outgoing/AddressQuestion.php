<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\SelectQuestion;

class AddressQuestion extends SelectQuestion
{
    public const NOT_LISTED = '-';

    /**
     * Construct a language selection question with the provided languages list.
     *
     * @param array $availableLanguages
     */
    public function __construct(array $field, array $addresses)
    {
        $addresses = array_map(function ($address) {
            return [
                'display' => $address['displayName'],
                'value' => [
                    'lat' => $address['latitude'],
                    'lon' => $address['longitude'],
                ],
            ];
        }, $addresses);

        $addresses[] = [
            'display' => __('conversation.geolocation.searchAgain'),
            'value' => self::NOT_LISTED,
        ];

        $field['options'] = $addresses;

        parent::__construct($field, 'value', 'display');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName(): string
    {
        return 'address';
    }

    public function shouldBeSentToPlaform(): bool
    {
        return $this->getValidatedAnswerValue() != self::NOT_LISTED;
    }

    public function createsNewQuestion(): bool
    {
        return $this->getValidatedAnswerValue() == self::NOT_LISTED;
    }

    public function getNextQuestion(): FieldQuestion
    {
        unset($this->field['options']);

        return new GeoLocation($this->field);
    }
}
