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

    public function toString(): string
    {
        return "{$this->getValueAsString()} {$this->text}";
    }

    public function getValueAsString(): string
    {
        return "[{$this->value}]";
    }

    public static function cancel()
    {
        return  new self(__('conversation.screen.cancel.value'), __('conversation.screen.cancel.text'));
    }

    public static function previous()
    {
        return new self(__('conversation.screen.previous.value'), __('conversation.screen.previous.text'));
    }

    public static function next()
    {
        return new self(__('conversation.screen.next.value'), __('conversation.screen.next.text'));
    }
}
