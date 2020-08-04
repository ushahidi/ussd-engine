<?php

namespace App\Messages\Outgoing\Screen;

class Option
{
    public $value;
    public $text;

    public function __construct(string $value, string $text)
    {
        $this->value = $value;
        $this->text = $text;
    }

    public function getText()
    {
        return "\n[{$this->value}] {$this->text}";
    }
}
