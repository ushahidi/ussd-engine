<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\SelectQuestion;

class Select extends SelectQuestion
{
    public function getAttributeName(): string
    {
        return 'select';
    }

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
        return false;
    }

    public function getHints(): string
    {
        return __('conversation.hints.select');
    }
}
