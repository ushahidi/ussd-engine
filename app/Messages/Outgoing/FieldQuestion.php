<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\Validator;

abstract class FieldQuestion extends Question implements FieldQuestionInterface
{
    protected $field;

    protected $name;

    public function __construct(array $field)
    {
        $this->field = $field;

        if (isset($this->field['name'])) {
            $this->name = $this->field['name'];
        }

        parent::__construct($this->getTextContent());
    }

    public function getTextContent(): string
    {
        return $this->field['label'];
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    abstract public function getAnswerBody(Answer $answer): array;

    public function setAnswer(Answer $answer)
    {
        $validated = $this->validate($this->getAnswerBody($answer));
        $this->answerValue = $validated[$this->name];
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

    abstract public function getAnswerValue();
}
