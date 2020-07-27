<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\TextQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Tests\TestCase;

class TextQuestionTest extends TestCase
{
    protected $field;

    public function setUp()
    {
        parent::setUp();

        $this->field = [
            'id' => 1,
            'type' => 'varchar',
            'key' => 'text-field',
            'label' => 'Text field',
            'instructions' => 'Some helpful instructions',
            'required' => true,
        ];
    }

    public function test_it_adds_required_rule_if_field_indicates_so()
    {
        $this->field['required'] = true;
        $textQuestion = new TextQuestion($this->field);

        $this->assertContains('required', $textQuestion->getRules());
    }

    public function test_it_does_not_add_required_rule_if_field_does_not_indicates_so()
    {
        $this->field['required'] = false;
        $textQuestion = new TextQuestion($this->field);

        $this->assertNotContains('required', $textQuestion->getRules());
    }

    public function test_it_returns_text_from_answer_as_value_from_answer()
    {
        $textQuestion = new TextQuestion($this->field);
        $text = 'Answer value';
        $answer = Answer::create($text);

        $this->assertEquals($text, $textQuestion->getValueFromAnswer($answer));
    }
}
