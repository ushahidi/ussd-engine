<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\SelectQuestion;

class SelectLanguageQuestion extends SelectQuestion
{
    /**
     * Construct a language selection question with the provided languages list.
     *
     * @param array $availableLanguages
     */
    public function __construct(array $availableLanguages)
    {
        $field = [
          'label' => __('conversation.chooseALanguage'),
          'key' => 'language',
          'name' =>  __('fields.language'),
          'required' => true,
          'options' => $availableLanguages,
        ];
        parent::__construct($field);
    }

    /**
     * Returns the selected language.
     *
     * @return string|null
     */
    public function getAnswerValue()
    {
        return $this->answerValue ? $this->optionsMap[$this->answerValue] : null;
    }
}
