<?php

namespace App\Messages\Outgoing\Fields;

use App\Messages\Outgoing\SelectQuestion;

class RadioButtons extends SelectQuestion
{
    /**
     * Sets the translated name for this field
     * before parent constructor is executed.
     *
     * @param array $field
     */
    public function __construct(array $field)
    {
        $field['name'] = __('fields.radioButtons');
        parent::__construct($field);
    }
}
