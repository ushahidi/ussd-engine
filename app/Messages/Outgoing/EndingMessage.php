<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Attachments\Attachment;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

/**
 * This class is used to represent the final message of a conversation.
 * It includes all and behaves the same way any other OutgoingMessage does.
 */
class EndingMessage extends OutgoingMessage
{
    /**
     * @param string $message
     * @param Attachment $attachment
     * @return EndingMessage
     */
    public static function create($message = null, Attachment $attachment = null)
    {
        return new self($message, $attachment);
    }
}
