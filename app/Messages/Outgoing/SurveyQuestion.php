<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\SelectQuestion;

class SurveyQuestion extends SelectQuestion
{
    /**
     * Construct a language selection question with the provided languages list.
     *
     * @param array $availableSurveys
     */
    public function __construct(array $availableSurveys)
    {
        $field = [
            'label' => __('conversation.selectSurvey'),
            'key' => 'survey',
            'required' => true,
            'options' => $availableSurveys,
        ];
        parent::__construct($field, 'id', 'name');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName(): string
    {
        return 'survey';
    }
}
