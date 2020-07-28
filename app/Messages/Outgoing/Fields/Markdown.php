<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Markdown extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'markdown';
    }

    public function getRules(): array
    {
        $textQuestionRules = parent::getRules();
        $markdownRules = ['string'];
        $rules = array_merge($markdownRules, $textQuestionRules);

        return $rules;
    }
}
