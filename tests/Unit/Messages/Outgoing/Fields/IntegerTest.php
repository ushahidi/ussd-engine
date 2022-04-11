<?php

namespace Tests\Unit\Messages\Outgoing\Fields;

use App\Messages\Outgoing\Fields\Integer;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class IntegerTest extends TestCase
{
    protected $integerQuestion;

    protected $answer;

    public function setUp()
    {
        parent::setUp();

        $field = [
            'id' => 1,
            'type' => 'integer',
            'label' => 'Integer',
            'required' => true,
        ];
        $this->integerQuestion = new Integer($field);
        $this->answer = new Answer();
    }

    public function test_answer_should_be_a_valid_integer()
    {
        $integer = '10';
        $this->answer->setText($integer);

        $this->integerQuestion->setAnswer($this->answer);

        $this->assertEquals($integer, $this->integerQuestion->getValidatedAnswerValue());
    }

    public function test_it_throws_error_if_answer_is_not_a_number()
    {
        $this->answer->setText('10.50');

        try {
            $this->integerQuestion->setAnswer($this->answer);
        } catch (\Throwable $ex) {
            $this->assertValidationError('is not a valid integer', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }
}
