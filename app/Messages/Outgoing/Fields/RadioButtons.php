<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\SelectQuestion;

class RadioButtons extends SelectQuestion
{
    public function getAttributeName(): string
    {
        return 'radio buttons';
    }
}
