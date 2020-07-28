<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\EndingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Tests\TestCase;

class EndingMessageTest extends TestCase
{
    public function test_it_creates_outgoing_message_instance()
    {
        $message = 'Good bye.';
        $endingMessage = EndingMessage::create($message);

        $this->assertInstanceOf(OutgoingMessage::class, $endingMessage);
        $this->assertEquals($message, $endingMessage->getText());
    }
}
