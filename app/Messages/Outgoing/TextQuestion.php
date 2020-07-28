<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;

class TextQuestion extends FieldQuestion
{
    /**
     * {@inheritdoc}
     */
    public function getAttributeName(): string
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getRules(): array
    {
        $rules = [];

        if ($this->field['required']) {
            $rules[] = 'required';
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFromAnswer(Answer $answer)
    {
        return $answer->getText();
    }
}
