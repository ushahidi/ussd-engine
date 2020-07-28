<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\FieldQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class FieldQuestionTest extends TestCase
{
    protected $field;

    protected $fieldQuestionMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field = [
            'id' => 1,
            'type' => 'varchar',
            'key' => 'test-field',
            'label' => 'My awesome field',
            'instructions' => 'Some helpful instructions',
            'translations' => [
                'en' => [
                    'label' => 'My awesome field in English',
                    'instructions' => 'Some helpful instructions in English',
                ],
                'es' => [
                    'label' => 'My awesome field in Spanish',
                    'instructions' => 'Some helpful instructions in Spanish',
                ],
            ],
        ];
        $this->fieldQuestionMock = $this->getMockForAbstractClass(
            FieldQuestion::class,
            ['field' => $this->field],
            '',
            true,
            true,
            true,
            ['validate']
        );
    }

    public function test_sets_question_text()
    {
        $expected = $this->field['translations']['en']['label'];
        $actual = $this->fieldQuestionMock->getText();
        $this->assertEquals($expected, $actual);
    }

    public function test_it_returns_the_expected_text_content()
    {
        $expected = $this->field['translations']['en']['label'];
        $actual = $this->fieldQuestionMock->getTextContent();
        $this->assertEquals($expected, $actual);
    }

    public function test_it_returns_the_expected_instructions()
    {
        $expected = $this->field['translations']['en']['instructions'];
        $actual = $this->fieldQuestionMock->getMoreInfoContent();
        $this->assertEquals($expected, $actual);
    }

    public function test_has_more_info_returns_true_if_it_does()
    {
        $actual = $this->fieldQuestionMock->hasMoreInfo();
        $this->assertTrue($actual);
    }

    public function test_has_more_info__returns_false_if_it_does_not()
    {
        unset($this->field['instructions']);
        unset($this->field['translations']);

        $fieldQuestionMock = $this->getMockForAbstractClass(FieldQuestion::class, ['field' => $this->field]);
        $this->assertFalse($fieldQuestionMock->hasMoreInfo());
    }

    public function test_it_translates_to_the_locale_set()
    {
        $locale = 'es';
        App::setLocale($locale);
        $expected = $this->field['translations'][$locale]['label'];
        $actual = FieldQuestion::translate('label', $this->field);
        $this->assertEquals($expected, $actual);
    }

    public function test_translate_returns_original_value_if_not_translations()
    {
        unset($this->field['translations']);
        $expected = $this->field['label'];
        $actual = FieldQuestion::translate('label', $this->field);
        $this->assertEquals($expected, $actual);
    }

    public function test_translate_returns_original_value_if_key_is_not_in_translations()
    {
        $this->field['new-key'] = 'The original value';
        $expected = $this->field['new-key'];
        $actual = FieldQuestion::translate('new-key', $this->field);
        $this->assertEquals($expected, $actual);
    }

    public function test_translate_returns_empty_string_if_key_is_null()
    {
        $this->field['null-key'] = null;
        $this->assertEquals('', FieldQuestion::translate('null-key', $this->field));
    }

    public function test_translate_returns_empty_string_if_key_is_not_set()
    {
        $this->assertEquals('', FieldQuestion::translate('this-key-is-not-set', $this->field));
    }

    public function test_has_translations_returns_true_if_it_does()
    {
        $actual = FieldQuestion::hasTranslations($this->field);
        $this->assertTrue($actual);
    }

    public function test_has_translations_returns_false_if_it_does_not()
    {
        unset($this->field['translations']);
        $actual = FieldQuestion::hasTranslations($this->field);
        $this->assertFalse($actual);
    }

    public function test_returns_the_correct_payload()
    {
        $value = 'This is the value';
        $answer = new Answer($value);
        $this->fieldQuestionMock->method('validate')->willReturn($value);

        $expected = [
            'id' => $this->field['id'],
            'type' => $this->field['type'],
            'value' => ['value' => $value],
        ];

        $this->fieldQuestionMock->setAnswer($answer);
        $actual = $this->fieldQuestionMock->toPayload();

        $this->assertEquals($expected, $actual);
    }

    public function test_it_sets_and_validate_answer()
    {
        $text = 'Answer text';
        $answer = new Answer($text);
        $this->fieldQuestionMock->expects($this->once())
                                ->method('validate')
                                ->with($this->equalTo($answer))
                                ->willReturn($text);

        $this->fieldQuestionMock->setAnswer($answer);

        $this->assertEquals($text, $this->fieldQuestionMock->getValidatedAnswerValue());
    }
}
