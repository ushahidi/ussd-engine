<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\SelectQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\Rule;

class Categories extends SelectQuestion
{
    public function __construct(array $field)
    {
        $field['name'] = __('fields.categories');
        parent::__construct($field, 'id', 'tag');
    }

    public function getRules(): array
    {
        $validationRules = [];

        if ($this->field['required']) {
            $validationRules[] = 'required';
        }

        $validationRules[] = 'array';

        $rules = [
        $this->name  => $validationRules,
        $this->name.'.*'  => Rule::in(array_keys($this->optionsMap)),
      ];

        return $rules;
    }

    public function getAnswerBody(Answer $answer): array
    {
        $value = $answer->isInteractiveMessageReply() ? $answer->getValue() : $answer->getText();

        return [$this->name => explode(',', $value)];
    }

    public function getAnswerValue()
    {
        $values = [];
        foreach ($this->answerValue as $option) {
            $values[] = $this->optionsMap[$option];
        }

        return [
            'value' => $values,
        ];
    }
}
