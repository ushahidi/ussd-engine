<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class LongText extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'long text';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $longTextQuestionRules = [
          'string',
        ];

        $rules = array_merge($textQuestionRules, $longTextQuestionRules);

        return $rules;
    }
}
