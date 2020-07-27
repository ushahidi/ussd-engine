<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Title extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'title';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $titleQuestionRules = [
          'string',
          'min:2',
          'max:150',
        ];

        $rules = array_merge($textQuestionRules, $titleQuestionRules);

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
