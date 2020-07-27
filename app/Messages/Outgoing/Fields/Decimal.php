<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Decimal extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'decimal';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $decimalQuestionRules = ['numeric'];
        $rules = array_merge($decimalQuestionRules, $textQuestionRules);

        return $rules;
    }
}
