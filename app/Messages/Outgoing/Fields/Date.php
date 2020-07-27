<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Date extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'date';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $dateQuestionRules = ['date'];
        $rules = array_merge($textQuestionRules, $dateQuestionRules);

        return $rules;
    }

    public function shouldShowHintsByDefault(): bool
    {
        return true;
    }

    public function hasHints(): bool
    {
        return true;
    }

    public function getHints(): string
    {
        return __('conversation.hints.date');
    }
}
