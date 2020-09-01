<?php

namespace App\Messages\Outgoing;

/**
 * This class is used to represent the final screen of a conversation.
 * It includes all and behaves the same way any other MessageScreen does.
 */
class LastScreen extends MessageScreen
{
    public function __construct(string $text)
    {
        parent::__construct($text, false);
    }
}
