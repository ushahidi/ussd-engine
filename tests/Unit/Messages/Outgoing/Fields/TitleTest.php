<?php

namespace Tests\Unit\Messages\Outgoing\Fields;

use App\Messages\Outgoing\Fields\Title;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TitleTest extends TestCase
{
    protected $titleQuestion;

    protected $answer;

    public function setUp()
    {
        parent::setUp();

        $field = [
            'id' => 1,
            'type' => 'title',
            'label' => 'Title',
            'required' => true,
        ];
        $this->titleQuestion = new Title($field);
        $this->answer = new Answer();
    }

    public function test_answer_should_be_at_least_two_characters()
    {
        $this->answer->setText('a');

        try {
            $this->titleQuestion->setAnswer($this->answer);
        } catch (\Throwable $ex) {
            $this->assertValidationError('must be at least 2 characters', $ex);
        }
    }

    public function test_answer_should_be_max_150_characters()
    {
        $this->answer->setText(str_repeat('a', 151));

        try {
            $this->titleQuestion->setAnswer($this->answer);
        } catch (\Throwable $ex) {
            $this->assertValidationError('may not be greater than 150 characters', $ex);
        }
    }
}
