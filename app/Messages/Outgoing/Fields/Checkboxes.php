<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\SelectQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\Rule;

class Checkboxes extends SelectQuestion
{
    public function getAttributeName(): string
    {
        return 'checkboxes';
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

    public function getValidatedAnswerValue(): array
    {
        $selectedOptions = [];
        foreach ($this->answerValue as $option) {
            $selectedOptions[] = $this->optionsMap[$option];
        }

        return $selectedOptions;
    }

    /**
     * Used to know if the hints for this question should be shown by default.
     *
     * @return bool
     */
    public function shouldShowHintsByDefault(): bool
    {
        return true;
    }

    /**
     * Used to know if this question has hints to show.
     *
     * @return bool
     */
    public function hasHints(): bool
    {
        return true;
    }

    /**
     * Return the hints to show for this field.
     *
     * @return string
     */
    public function getHints(): string
    {
        return __('conversation.hints.checkboxes');
    }
}
