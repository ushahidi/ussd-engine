<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\SelectQuestion;

class Select extends SelectQuestion
{
    public function getAttributeName(): string
    {
        return 'select';
    }
}
