<?php

namespace App\Messages\Outgoing\Screen;

class Option
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $text;

    public function __construct(string $value, string $text)
    {
        $this->value = $value;
        $this->text = $text;
    }

    public function getText(): string
    {
        return "\n[{$this->value}] {$this->text}";
    }

    public function toString(): string
    {
        return "\n[{$this->value}] {$this->text}";
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }
}
