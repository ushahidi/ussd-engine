<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\Fields\Checkboxes;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class CheckboxesTest extends TestCase
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
        $checkboxesQuestion = new Checkboxes($this->field);
        foreach ($checkboxesQuestion->getButtons() as $index => $button) {
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
        $checkboxesQuestion = new Checkboxes($this->field, 'id', 'name');
        foreach ($checkboxesQuestion->getButtons() as $index => $button) {
            $this->assertEquals($options[$index]['name'], $button['text']);
        }
    }

    public function test_it_validates_answer_is_required_if_field_indicates_so()
    {
        $this->field['required'] = true;
        $checkboxesQuestion = new Checkboxes($this->field);

        try {
            $checkboxesQuestion->setAnswer(Answer::create());
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
        $checkboxesQuestion = new Checkboxes($this->field);

        $checkboxesQuestion->setAnswer(Answer::create());

        $this->assertEquals([], $checkboxesQuestion->getValidatedAnswerValue());
    }

    public function test_it_returns_selected_options_from_answer_as_value()
    {
        $checkboxesQuestion = new Checkboxes($this->field);
        $answer = Answer::create('1');

        $this->assertEquals(['1'], $checkboxesQuestion->getValueFromAnswer($answer));
    }

    public function test_it_returns_multiple_selected_options_from_answer_as_value()
    {
        $checkboxesQuestion = new Checkboxes($this->field);
        $answer = Answer::create('1,2');

        $this->assertEquals(['1', '2'], $checkboxesQuestion->getValueFromAnswer($answer));
    }

    public function test_it_returns_answer_value_when_interactive_reply()
    {
        $checkboxesQuestion = new Checkboxes($this->field);
        $answer = new Answer();
        $answer->setInteractiveReply(true);
        $answer->setValue('1');

        $this->assertEquals(['1'], $checkboxesQuestion->getValueFromAnswer($answer));
    }

    public function test_it_does_not_accept_invalid_options()
    {
        $this->field['options'] = ['A', 'B', 'C'];
        $checkboxesQuestion = new Checkboxes($this->field);

        try {
            $checkboxesQuestion->setAnswer(Answer::create('4'));
        } catch (\Throwable $ex) {
            $this->assertValidationError('is invalid', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_not_accept_invalid_options_when_selecting_multiple()
    {
        $this->field['options'] = ['A', 'B', 'C'];
        $checkboxesQuestion = new Checkboxes($this->field);

        try {
            $checkboxesQuestion->setAnswer(Answer::create('10,4'));
        } catch (\Throwable $ex) {
            $this->assertValidationError('is invalid', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_requires_all_the_options_to_be_valid_when_selecting_multiple()
    {
        $this->field['options'] = ['A', 'B', 'C'];
        $checkboxesQuestion = new Checkboxes($this->field);

        try {
            $checkboxesQuestion->setAnswer(Answer::create('1,5,3'));
        } catch (\Throwable $ex) {
            $this->assertValidationError('is invalid', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_accept_valid_options()
    {
        $this->field['options'] = ['A', 'B', 'C'];
        $checkboxesQuestion = new Checkboxes($this->field);

        $checkboxesQuestion->setAnswer(Answer::create('2'));

        $this->assertEquals(['B'], $checkboxesQuestion->getValidatedAnswerValue());
    }

    public function test_it_does_accept_multiple_valid_options()
    {
        $this->field['options'] = ['A', 'B', 'C'];
        $checkboxesQuestion = new Checkboxes($this->field);

        $checkboxesQuestion->setAnswer(Answer::create('2,3'));

        $this->assertEquals(['B', 'C'], $checkboxesQuestion->getValidatedAnswerValue());
    }
}
