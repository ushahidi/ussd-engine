<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class ShortText extends TextQuestion
{
    public function getRules(): array
    {
        $rules = parent::getRules();
        $validationRules = [
          'string',
          'max:255',
        ];

        $rules[$this->field['key']] = array_merge($rules[$this->field['key']], $validationRules);

        return $rules;
    }
}
