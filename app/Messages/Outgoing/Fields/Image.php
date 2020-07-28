<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Image extends TextQuestion
{
    public function getAttributeName(): string
    {
        return 'image';
    }
}
