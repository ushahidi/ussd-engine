<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Attachments\Attachment;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

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
