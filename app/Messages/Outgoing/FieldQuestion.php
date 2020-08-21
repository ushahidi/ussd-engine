<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

abstract class FieldQuestion extends Question implements FieldQuestionInterface
{
    /**
     * The field data.
     *
     * @var array
     */
    protected $field;

    protected $answerValue;

    public function __construct(array $field)
    {
        $this->field = $field;
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

    /**
     * Returns the attribute name to be use when performing
     * translations and validations.
     *
     * Make sure you add this attribute with the custom atribute name
     * in your lang files.
     *
     * @return string
     */
    abstract public function getAttributeName(): string;

    /**
     * Extract the value to be  validated from the Answer object.
     *
     * @param Answer $answer
     * @return mixed
     */
    abstract public function getValueFromAnswer(Answer $answer);

    /**
     * Extract, validate and set the value from the provided Answer object.
     *
     * @param Answer $answer
     * @return void
     */
    public function setAnswer(Answer $answer)
    {
        $this->answerValue = $this->validate($answer);
    }

    /**
     * Extract, validate and return the value from the provided Answer class.
     *
     * It uses the rules, data and messages returned by each respective method.
     *
     * @param Answer $answer
     * @return mixed
     */
    public function validate(Answer $answer)
    {
        $attributeName = $this->getAttributeName();
        $data = [$attributeName => $this->getValueFromAnswer($answer)];

        $rules = $this->getRules();

        if (! Arr::isAssoc($rules)) {
            $rules = [$attributeName => $rules];
        }

        $messages = $this->getValidationMessages();

        $validator = Validator::make($data, $rules, $messages);

        $validated = $validator->validate();

        return $validated[$attributeName];
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

    /**
     * Return an array of validation rules to be used when validating the answer input.
     *
     * @return array
     */
    abstract public function getRules(): array;

    public function shouldBeSentToPlaform(): bool
    {
        return true;
    }

    public function createsNewQuestion(): bool
    {
        return false;
    }

    public function getNextQuestion(): self
    {
        throw new Exception('No next question available for this question.');
    }

    /**
     * Returns the field body to attach to the survey report payload.
     *
     * @return array
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->field['id'],
            'type' => $this->field['type'],
            'value' => [
                'value' => $this->getValidatedAnswerValue(),
            ],
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

    /**
     * Returns the value obtained from the answer.
     *
     * @return mixed
     */
    public function getValidatedAnswerValue()
    {
        return $this->answerValue;
    }

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

    /**
     * Indicates if this question is required or not.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->field['required'];
    }
}
