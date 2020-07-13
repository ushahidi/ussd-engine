<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class LongText extends TextQuestion
{
    public function getRules(): array
    {
        $rules = parent::getRules();
        $validationRules = [
          'string',
        ];

        $rules[$this->field['key']] = array_merge($rules[$this->field['key']], $validationRules);

        return $rules;
    }
}
