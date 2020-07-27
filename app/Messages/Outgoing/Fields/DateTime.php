<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class DateTime extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'datetime';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $datetimeQuestionRules = ['date'];
        $rules = array_merge($textQuestionRules, $datetimeQuestionRules);

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
