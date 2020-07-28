<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Description extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'description';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $descriptionQuestionRules = [
          'string',
        ];

        $rules = array_merge($textQuestionRules, $descriptionQuestionRules);

        return $rules;
    }
}
