<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class ShortText extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'short text';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $shortTextRules = [
          'string',
          'max:255',
        ];

        $rules = array_merge($textQuestionRules, $shortTextRules);

        return $rules;
    }
}
