<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SelectQuestion extends FieldQuestion
{
    protected $optionsMap = [];

    protected $valueAccessor;

    protected $displayAccessor;

    public function __construct(array $field, string $valueAccessor = null, string $displayAccessor = null)
    {
        parent::__construct($field);
        $this->valueAccessor = $valueAccessor;
        $this->displayAccessor = $displayAccessor;

        $this->addButtons($this->getButtons());
    }

    public function getButtons(): array
    {
        $buttons = [];
        foreach ($this->field['options'] as $index => $value) {
            $optionDisplay = $this->displayAccessor ? Arr::get($value, $this->displayAccessor) : $value;
            $optionValue = $this->valueAccessor ? Arr::get($value, $this->valueAccessor) : $value;
            $option = $index + 1;
            $this->optionsMap[$option] = $optionValue;
            $buttons[] = Button::create($optionDisplay)->value($option);
        }

        return $buttons;
    }

    public function getRules(): array
    {
        $validationRules = [];

        if ($this->field['required']) {
            $validationRules[] = 'required';
        }

        $validationRules[] = Rule::in(array_keys($this->optionsMap));

        $rules = [
          $this->field['key']  => $validationRules,
        ];

        return $rules;
    }

    public function getAnswerBody(Answer $answer): array
    {
        $value = $answer->isInteractiveMessageReply() ? $answer->getValue() : $answer->getText();

        return [$this->field['key'] => $value];
    }

    public function getAnswerResponse(): array
    {
        return [
          'value' => $this->answerValue ? $this->optionsMap[$this->answerValue] : null,
        ];
    }
}
