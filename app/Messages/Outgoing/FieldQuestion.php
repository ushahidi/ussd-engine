<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\Validator;

class FieldQuestion extends Question implements FieldQuestionInterface
{
    protected $field;

    public function __construct(array $field)
    {
        $this->field = $field;

        parent::__construct($this->field['label']);
    }

    public function setAnswer(Answer $answer)
    {
        $validated = $this->validate($answer);
        $this->answerValue = $validated[$this->field['key']];
    }

    public function validate(Answer $answer)
    {
        $rules = $this->getRules();

        $validator = Validator::make([
            $this->field['key'] => $answer->getText(),
        ], $rules);

        return $validator->validate();
    }

    public function getRules(): array
    {
        $validationRules = [];

        if ($this->field['required']) {
            $validationRules[] = 'required';
        }

        $validationRules = implode('|', $validationRules);

        $rules = [
          $this->field['key']  => $validationRules,
        ];

        return $rules;
    }

    public function getAnswerResponse(): array
    {
        return [
          'id' => $this->field['id'],
          'type' => $this->field['type'],
          'value' => $this->answerValue,
        ];
    }
}
