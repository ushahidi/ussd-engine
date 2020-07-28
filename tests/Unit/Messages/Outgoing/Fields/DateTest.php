<?php

namespace Tests\Unit\Messages\Outgoing\Fields;

use App\Messages\Outgoing\Fields\Date;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class DateTest extends TestCase
{
    protected $dateQuestion;

    protected $answer;

    public function setUp()
    {
        parent::setUp();

        $field = [
            'id' => 1,
            'type' => 'date',
            'label' => 'Date',
            'required' => true,
        ];
        $this->dateQuestion = new Date($field);
        $this->answer = new Answer();
    }

    public function test_answer_should_be_a_valid_date()
    {
        $date = '2020-01-01';
        $this->answer->setText($date);

        $this->dateQuestion->setAnswer($this->answer);

        $this->assertEquals($date, $this->dateQuestion->getValidatedAnswerValue());
    }

    public function test_it_throws_error_if_answer_is_not_a_valid_date()
    {
        $this->answer->setText('The day before yesterday');

        try {
            $this->dateQuestion->setAnswer($this->answer);
        } catch (\Throwable $ex) {
            $this->assertValidationError('is not a valid date', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }
}
