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

    /**
     * Returns the array of buttons that can be attached to this question
     * using the options in the field.
     *
     * If the options are of type array, the value and display accessors are used
     * to set the Button value and display text respectively.
     *
     * If not, the options are used for both the Button value and display.
     *
     * Important: The end user will be always prompted to send a number. All the posible
     * options are mapped with numbers, so the end user doesn't have to type the whole
     * value. Also, this allow us to work with an universal language when working with
     * multiple languages.
     *
     * @return array
     */
    public function getButtons(): array
    {
        $buttons = [];
        foreach ($this->field['options'] as $index => $value) {
            if (is_array($value) && $this->displayAccessor) {
                $optionDisplay = $this->translate($this->displayAccessor, $value);
            } else {
                $optionDisplay = $this->translate('options.'.$index, $this->field);
            }
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
          $this->name  => $validationRules,
        ];

        return $rules;
    }

    public function getAnswerBody(Answer $answer): array
    {
        $value = $answer->isInteractiveMessageReply() ? $answer->getValue() : $answer->getText();

        return [$this->name => $value];
    }

    public function getAnswerValue()
    {
        return [
          'value' => $this->answerValue ? $this->optionsMap[$this->answerValue] : null,
        ];
    }
}
