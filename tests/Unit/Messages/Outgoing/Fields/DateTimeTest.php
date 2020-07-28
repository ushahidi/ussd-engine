<?php

namespace Tests\Unit\Messages\Outgoing\Fields;

use App\Messages\Outgoing\Fields\DateTime;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class DateTimeTest extends TestCase
{
    protected $dateTimeQuestion;

    protected $answer;

    public function setUp()
    {
        parent::setUp();

        $field = [
            'id' => 1,
            'type' => 'dateTime',
            'label' => 'DateTime',
            'required' => true,
        ];
        $this->dateTimeQuestion = new DateTime($field);
        $this->answer = new Answer();
    }

    public function test_answer_should_be_a_valid_dateTime()
    {
        $dateTime = '2020-01-01';
        $this->answer->setText($dateTime);

        $this->dateTimeQuestion->setAnswer($this->answer);

        $this->assertEquals($dateTime, $this->dateTimeQuestion->getValidatedAnswerValue());
    }

    public function test_it_throws_error_if_answer_is_not_a_valid_dateTime()
    {
        $this->answer->setText('The day before yesterday');

        try {
            $this->dateTimeQuestion->setAnswer($this->answer);
        } catch (\Throwable $ex) {
            $this->assertValidationError('is not a valid date', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }
}
