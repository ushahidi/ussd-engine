<?php

namespace App\Messages\Outgoing;

class FieldQuestionFactory
{
    public static function create(array $field): FieldQuestion
    {
        return new FieldQuestion($field);
    }
}
