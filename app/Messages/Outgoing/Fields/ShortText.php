<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class ShortText extends TextQuestion
{
    /**
     * Sets the translated name for this field
     * before parent constructor is executed.
     *
     * @param array $field
     */
    public function __construct(array $field)
    {
        $field['name'] = __('fields.shortText');
        parent::__construct($field);
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $validationRules = [
          'string',
          'max:255',
        ];

        $rules[$this->name] = array_merge($rules[$this->name], $validationRules);

        return $rules;
    }
}
