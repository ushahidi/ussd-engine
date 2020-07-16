<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Title extends TextQuestion
{
    /**
     * Sets the translated name for this field
     * before parent constructor is executed.
     *
     * @param array $field
     */
    public function __construct(array $field)
    {
        $field['name'] = __('fields.title');
        parent::__construct($field);
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $validationRules = [
          'string',
          'max:150',
        ];

        $rules[$this->name] = array_merge($rules[$this->name], $validationRules);

        return $rules;
    }

    /**
     * Returns the array of translated errors to use with the validator of this field.
     *
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [
            'required' => __('validation.custom.title.required'),
        ];
    }
}
