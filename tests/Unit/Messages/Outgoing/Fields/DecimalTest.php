<?php

namespace Tests\Unit\Messages\Outgoing\Fields;

use App\Messages\Outgoing\Fields\Decimal;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class DecimalTest extends TestCase
{
    protected $decimalQuestion;

    protected $answer;

    public function setUp()
    {
        parent::setUp();

        $field = [
            'id' => 1,
            'type' => 'decimal',
            'label' => 'Decimal',
            'required' => true,
        ];
        $this->decimalQuestion = new Decimal($field);
        $this->answer = new Answer();
    }

    public function test_answer_should_be_a_valid_decimal()
    {
        $decimal = '10.50';
        $this->answer->setText($decimal);

        $this->decimalQuestion->setAnswer($this->answer);

        $this->assertEquals($decimal, $this->decimalQuestion->getAnswerValue());
    }

    public function test_it_throws_error_if_answer_is_not_a_number()
    {
        $this->answer->setText('number');

        try {
            $this->decimalQuestion->setAnswer($this->answer);
        } catch (\Throwable $ex) {
            $this->assertValidationError(' must be a number', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }
}
