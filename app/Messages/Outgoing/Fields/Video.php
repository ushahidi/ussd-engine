<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\TextQuestion;

class Video extends TextQuestion
{
    /**
     * {@inheritdoc}
     */
    public function getAttributeName(): string
    {
        return 'video';
    }
}
