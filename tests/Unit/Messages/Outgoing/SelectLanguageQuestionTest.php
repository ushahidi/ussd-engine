<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\SelectLanguageQuestion;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelectLanguageQuestionTest extends TestCase
{
    protected $languages;

    /**
     * @var  \App\Messages\Outgoing\SelectLanguageQuestion
     */
    protected $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languages = ['en', 'es'];
        $this->question = new SelectLanguageQuestion($this->languages);
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
}
