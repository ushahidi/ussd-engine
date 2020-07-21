<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

abstract class FieldQuestion extends Question implements FieldQuestionInterface
{
    protected $field;

    protected $name;

    protected $answerValue;

    public function __construct(array $field)
    {
        $this->field = $field;

        if (isset($this->field['name'])) {
            $this->name = $this->field['name'];
        }

        parent::__construct($this->getTextContent());
    }

    /**
     * Returns the string to be used as text for this question.
     *
     * @return string
     */
    public function getTextContent(): string
    {
        return self::translate('label', $this->field);
    }

    /**
     * Returns the string to be used when the user triggers more info
     * for this question.
     *
     * @return string
     */
    public function getMoreInfoContent(): string
    {
        return self::translate('instructions', $this->field);
    }

    /**
     * Returns true if the question has something to show when the user
     * ask for more info.
     *
     * @return bool
     */
    public function hasMoreInfo(): bool
    {
        return ! empty($this->getMoreInfoContent());
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

    /**
     * Returns the field body to attach to the survey report payload.
     *
     * @return array
     */
    public function getAnswerResponse(): array
    {
        return [
            'id' => $this->field['id'],
            'type' => $this->field['type'],
            'value' => $this->getAnswerValue(),
          ];
    }

    /**
     * Find the translation for the provided accesor in the provided context.
     * The current app locale is used to find the right set of translations.
     *
     * @param string $accesor
     * @param array $context
     * @return string
     */
    public static function translate(string $accesor, array $context): string
    {
        $defaultValue = Arr::get($context, $accesor);
        if (self::hasTranslations($context)) {
            $locale = App::getLocale();
            $translations = isset($context['translations'][$locale]) ? $context['translations'][$locale] : [];

            return (string) Arr::get($translations, $accesor, $defaultValue);
        }

        return (string) $defaultValue;
    }

    /**
     * Returns true if the provided context contains a list of translations,
     * returns false otherwise.
     *
     * @param array $context
     * @return bool
     */
    public static function hasTranslations(array $context): bool
    {
        return isset($context['translations']) && ! empty($context['translations']);
    }

    abstract public function getAnswerValue();

    /**
     * Used to know if the hints for this question should be shown by default.
     *
     * @return bool
     */
    public function shouldShowHintsByDefault(): bool
    {
        return false;
    }

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
