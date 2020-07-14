<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

abstract class FieldQuestion extends Question implements FieldQuestionInterface
{
    protected $field;

    public function __construct(array $field)
    {
        $this->field = $field;

        parent::__construct($this->getTextContent());
    }

    public function getTextContent(): string
    {
        return $this->__('label');
    }

    public function getMoreInfoContent(): string
    {
        return $this->__('instructions');
    }

    abstract public function getAnswerBody(Answer $answer): array;

    public function setAnswer(Answer $answer)
    {
        $validated = $this->validate($this->getAnswerBody($answer));
        $this->answerValue = $validated[$this->field['key']];
    }

    public function validate(array $body)
    {
        $rules = $this->getRules();

        $messages = $this->getValidationMessages();

        $validator = Validator::make($body, $rules, $messages);

        return $validator->validate();
    }

    /**
     * Returns the array of translated errors to use with the validator of this field.
     *
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [];
    }

    abstract public function getRules(): array;

    public function getAnswerResponse(): array
    {
        return [
            'id' => $this->field['id'],
            'type' => $this->field['type'],
            'value' => $this->getAnswerValue(),
          ];
    }

    public function __(string $accesor): string
    {
        $locale = App::getLocale();
        $translations = isset($this->field['translations'][$locale]) ? $this->field['translations'][$locale] : [];

        return (string) (isset($translations[$accesor]) ? $translations[$accesor] : $this->field[$accesor]);
    }

    abstract public function getAnswerValue();

    /**
     * Used to know if this question has hints to show.
     *
     * @return bool
     */
    public function hasHints(): bool
    {
        return false;
    }

    /**
     * Return the hints to show for this field.
     *
     * @return string
     */
    public function getHints(): string
    {
        return '';
    }
}
