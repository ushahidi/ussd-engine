<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\SelectQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\Rule;

class Categories extends SelectQuestion
{
    public function __construct(array $field)
    {
        parent::__construct($field, 'id', 'tag');
    }

    public function getAttributeName(): string
    {
        return 'categories';
    }

    public function getRules(): array
    {
        $attributeName = $this->getAttributeName();

        $checkboxesRules = [
            $this->field['required'] ? 'required' : 'nullable',
            'array',
        ];

        $rules = [
          $attributeName  => $checkboxesRules,
          $attributeName.'.*'  => Rule::in(array_keys($this->optionsMap)),
        ];

        return $rules;
    }

    public function getValueFromAnswer(Answer $answer)
    {
        $value = $answer->isInteractiveMessageReply() ? $answer->getValue() : $answer->getText();

        return $value ? explode(',', $value) : [];
    }

    public function getValidatedAnswerValue()
    {
        if (empty($this->answerValue)) {
            return [];
        }

        $selectedCategories = [];
        foreach ($this->answerValue as $option) {
            $selectedCategories[] = $this->optionsMap[$option];
        }

        return $selectedCategories;
    }
}
