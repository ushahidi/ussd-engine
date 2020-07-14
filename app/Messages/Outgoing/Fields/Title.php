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
