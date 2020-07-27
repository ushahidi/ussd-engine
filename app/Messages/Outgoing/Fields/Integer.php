<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Integer extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'integer';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $integerQuestionRules = ['integer'];
        $rules = array_merge($integerQuestionRules, $textQuestionRules);

        return $rules;
    }
}
