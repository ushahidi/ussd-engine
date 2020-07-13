<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Title extends TextQuestion
{
    public function getRules(): array
    {
        $rules = parent::getRules();
        $validationRules = [
          'string',
          'max:150',
        ];

        $rules[$this->field['key']] = array_merge($rules[$this->field['key']], $validationRules);

        return $rules;
    }
}
