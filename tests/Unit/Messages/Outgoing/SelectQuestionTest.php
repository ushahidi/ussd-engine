<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\SelectQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Select;
use Tests\TestCase;

class SelectQuestionTest extends TestCase
{
    protected $field;

    public function setUp()
    {
        parent::setUp();

        $this->field = [
            'id' => 1,
            'type' => 'varchar',
            'key' => 'select-field',
            'label' => 'Select field',
            'instructions' => 'Some helpful instructions',
            'required' => true,
            'options' => [],
        ];
    }

    public function test_it_add_a_button_for_each_option()
    {
        $options = ['Option 1', 'Option 2', 'Option 3'];
        $this->field['options'] = $options;
        $selectQuestion = new SelectQuestion($this->field);
        foreach ($selectQuestion->getButtons() as $index => $button) {
            $this->assertEquals($options[$index], $button['text']);
        }
    }

    public function test_it_uses_display_accesor_to_set_button_text()
    {
        $options = [
          [
              'id' => 1,
              'name' => 'Option 1',
          ],
          [
              'id' => 2,
              'name' => 'Option 2',
          ],
          [
              'id' => 3,
              'name' => 'Option 3',
          ],
        ];
        $this->field['options'] = $options;
        $selectQuestion = new SelectQuestion($this->field, 'id', 'name');
        foreach ($selectQuestion->getButtons() as $index => $button) {
            $this->assertEquals($options[$index]['name'], $button['text']);
        }
    }

    public function test_it_validates_answer_is_required_if_field_indicates_so()
    {
        $this->field['required'] = true;
        $selectQuestion = new SelectQuestion($this->field);

        try {
            $selectQuestion->setAnswer(Answer::create());
        } catch (\Throwable $ex) {
            $this->assertValidationError('is required', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_not_require_an_answer_if_field_does_not_indicates_so()
    {
        $this->field['required'] = false;
        $this->field['options'] = ['A', 'B', 'C'];
        $selectQuestion = new SelectQuestion($this->field);

        $selectQuestion->setAnswer(Answer::create());

        $this->assertNull($selectQuestion->getAnswerValue());
    }

    public function test_it_returns_text_from_answer_as_value()
    {
        $selectQuestion = new SelectQuestion($this->field);
        $text = '1';
        $answer = Answer::create($text);

        $this->assertEquals($text, $selectQuestion->getValueFromAnswer($answer));
    }

    public function test_it_returns_answer_value_when_interactive_reply()
    {
        $selectQuestion = new SelectQuestion($this->field);
        $value = '1';
        $answer = new Answer();
        $answer->setInteractiveReply(true);
        $answer->setValue($value);

        $this->assertEquals($value, $selectQuestion->getValueFromAnswer($answer));
    }

    public function test_it_does_not_accept_invalid_options()
    {
        $this->field['options'] = ['A', 'B', 'C'];
        $selectQuestion = new SelectQuestion($this->field);

        try {
            $selectQuestion->setAnswer(Answer::create('4'));
        } catch (\Throwable $ex) {
            $this->assertValidationError('selected option is invalid', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_accept_valid_options()
    {
        $this->field['options'] = ['A', 'B', 'C'];
        $selectQuestion = new SelectQuestion($this->field);

        $selectQuestion->setAnswer(Answer::create('2'));

        $this->assertEquals('B', $selectQuestion->getAnswerValue());
    }
}
