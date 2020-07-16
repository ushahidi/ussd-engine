<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class DateTime extends TextQuestion
{
    /**
     * Sets the translated name for this field
     * before parent constructor is executed.
     *
     * @param array $field
     */
    public function __construct(array $field)
    {
        $field['name'] = __('fields.datetime');
        parent::__construct($field);
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $validationRules = ['date'];
        $rules[$this->name] = array_merge($rules[$this->name], $validationRules);

        return $rules;
    }
}
