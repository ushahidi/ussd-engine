<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\LanguageQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LanguageQuestionTest extends TestCase
{
    protected $languages;

    /**
     * @var  \App\Messages\Outgoing\LanguageQuestion
     */
    protected $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languages = ['en', 'es'];
        $this->question = new LanguageQuestion($this->languages);
    }

    public function test_it_creates_the_select_question_with_languages()
    {
        $questionOptions = array_column($this->question->getActions(), 'name');
        $this->assertEqualsCanonicalizing($this->languages, $questionOptions);
    }

    public function test_it_creates_question_with_translated_text()
    {
        $this->assertEquals(__('conversation.chooseALanguage'), $this->question->getText());
    }

    public function test_it_validates_language_is_required()
    {
        $this->expectException(ValidationException::class);

        $this->question->setAnswer(new Answer());
    }

    public function test_it_validates_selected_language_is_valid()
    {
        $this->expectException(ValidationException::class);

        $this->question->setAnswer(new Answer('not-a-language'));
    }

    public function test_it_returns_a_valid_language_as_answer_value()
    {
        $this->question->setAnswer(new Answer('1'));
        $this->assertContains($this->question->getAnswerValue(), $this->languages);
    }

    public function test_it_returns_null_if_no_ansver()
    {
        $this->assertNull($this->question->getAnswerValue());
    }
}
