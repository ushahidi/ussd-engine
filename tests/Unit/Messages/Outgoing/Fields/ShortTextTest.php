<?php

namespace Tests\Unit\Messages\Outgoing\Fields;

use App\Messages\Outgoing\Fields\ShortText;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class ShortTextTest extends TestCase
{
    protected $shortTextQuestion;

    protected $answer;

    public function setUp()
    {
        parent::setUp();

        $field = [
            'id' => 1,
            'type' => 'varchar',
            'label' => 'Short Text',
            'required' => true,
        ];
        $this->shortTextQuestion = new ShortText($field);
        $this->answer = new Answer();
    }

    public function test_answer_should_be_max_255_characters()
    {
        $this->answer->setText(str_repeat('a', 256));

        try {
            $this->shortTextQuestion->setAnswer($this->answer);
        } catch (\Throwable $ex) {
            $this->assertValidationError('may not be greater than 255 characters', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }
}
