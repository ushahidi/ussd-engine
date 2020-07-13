<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Date extends TextQuestion
{
    public function getRules(): array
    {
        $rules = parent::getRules();
        $validationRules = [
          'date',
        ];

        $rules[$this->field['key']] = array_merge($rules[$this->field['key']], $validationRules);

        return $rules;
    }

    public function getAnswerValue()
    {
        return $this->answerValue;
    }
}
