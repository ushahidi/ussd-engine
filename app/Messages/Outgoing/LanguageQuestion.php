<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\SelectQuestion;

class LanguageQuestion extends SelectQuestion
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
            'required' => true,
            'options' => $availableLanguages,
        ];
        parent::__construct($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName(): string
    {
        return 'language';
    }

    public static function filterEnabledLanguages(array $languages): array
    {
        $enabledLanguages = config('settings.enabled_languages', []);
        return array_values(array_intersect($languages, $enabledLanguages));
    }
}
