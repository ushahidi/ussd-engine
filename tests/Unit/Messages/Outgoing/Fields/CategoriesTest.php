<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\Fields\Categories;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    protected $field;

    public function setUp()
    {
        parent::setUp();

        $this->field = [
            'id' => 1,
            'type' => 'tags',
            'key' => 'select-field',
            'label' => 'Select field',
            'instructions' => 'Some helpful instructions',
            'required' => true,
            'options' => [
                [
                    'id' => 1,
                    'tag' => 'Category 1',
                ],
                [
                    'id' => 2,
                    'tag' => 'Category 2',
                ],
                [
                    'id' => 3,
                    'tag' => 'Category 3',
                ],
            ],
        ];
    }

    public function test_it_add_a_button_for_each_category()
    {
        $categoriesQuestion = new Categories($this->field);
        foreach ($categoriesQuestion->getButtons() as $index => $button) {
            $this->assertEquals($this->field['options'][$index]['tag'], $button['text']);
        }
    }

    public function test_it_validates_answer_is_required_if_field_indicates_so()
    {
        $this->field['required'] = true;
        $categoriesQuestion = new Categories($this->field);

        try {
            $categoriesQuestion->setAnswer(Answer::create());
        } catch (\Throwable $ex) {
            $this->assertValidationError('is required', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_not_require_an_answer_if_field_does_not_indicates_so()
    {
        $this->field['required'] = false;
        $categoriesQuestion = new Categories($this->field);

        $categoriesQuestion->setAnswer(Answer::create());

        $this->assertEquals([], $categoriesQuestion->getValidatedAnswerValue());
    }

    public function test_it_returns_selected_categories_from_answer_as_value()
    {
        $categoriesQuestion = new Categories($this->field);
        $answer = Answer::create('1');

        $this->assertEquals(['1'], $categoriesQuestion->getValueFromAnswer($answer));
    }

    public function test_it_returns_multiple_selected_categories_from_answer_as_value()
    {
        $categoriesQuestion = new Categories($this->field);
        $answer = Answer::create('1,2');

        $this->assertEquals(['1', '2'], $categoriesQuestion->getValueFromAnswer($answer));
    }

    public function test_it_returns_answer_value_when_interactive_reply()
    {
        $categoriesQuestion = new Categories($this->field);
        $answer = new Answer();
        $answer->setInteractiveReply(true);
        $answer->setValue('1');

        $this->assertEquals(['1'], $categoriesQuestion->getValueFromAnswer($answer));
    }

    public function test_it_does_not_accept_invalid_categories()
    {
        $categoriesQuestion = new Categories($this->field);

        try {
            $categoriesQuestion->setAnswer(Answer::create('4'));
        } catch (\Throwable $ex) {
            $this->assertValidationError('is invalid', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_not_accept_invalid_categories_when_selecting_multiple()
    {
        $categoriesQuestion = new Categories($this->field);

        try {
            $categoriesQuestion->setAnswer(Answer::create('10,4'));
        } catch (\Throwable $ex) {
            $this->assertValidationError('is invalid', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_requires_all_the_categories_to_be_valid_when_selecting_multiple()
    {
        $categoriesQuestion = new Categories($this->field);

        try {
            $categoriesQuestion->setAnswer(Answer::create('1,5,3'));
        } catch (\Throwable $ex) {
            $this->assertValidationError('is invalid', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_accept_valid_categories()
    {
        $categoriesQuestion = new Categories($this->field);

        $categoriesQuestion->setAnswer(Answer::create('2'));

        $this->assertEquals([2], $categoriesQuestion->getValidatedAnswerValue());
    }

    public function test_it_does_accept_multiple_valid_categories()
    {
        $categoriesQuestion = new Categories($this->field);

        $categoriesQuestion->setAnswer(Answer::create('2,3'));

        $this->assertEquals([2, 3], $categoriesQuestion->getValidatedAnswerValue());
    }

    public function test_it_returns_empty_array_if_no_answer_was_set()
    {
        $categoriesQuestion = new Categories($this->field);

        $this->assertEquals([], $categoriesQuestion->getValidatedAnswerValue());
    }
}
