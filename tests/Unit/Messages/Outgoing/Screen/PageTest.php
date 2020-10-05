<?php

namespace Tests\Unit\Messages\Outgoing\Screen;

use App\Messages\Outgoing\Screen\Option;
use App\Messages\Outgoing\Screen\Page;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PageTest extends TestCase
{
    public $maxCharactersPerPage = 160;
    public $shortText = 'Choose one of the available languages:';
    public $longText = 'Be aware that not all surveys have all languages. Unfortunately this list isn’t filtered by country. Because of this, it may happen that some languages aren’t available to you. Choose one of the available languages:';
    public $screenOptions;
    public $questionOptions;

    public function setUp(): void
    {
        parent::setUp();
        Config::set('ussd.max_characters_per_page', $this->maxCharactersPerPage);

        $this->screenOptions = [
          new Option('C', 'Cancel'),
        ];

        $this->questionOptions = [
          new Option('1', 'English'),
          new Option('2', 'Spanish'),
          new Option('3', 'French'),
        ];
    }

    public function assertPageContentLengthIsCorrect(string $text)
    {
        $this->assertLessThanOrEqual(Config::get('ussd.max_characters_per_page'), mb_strlen($text));
    }

    public function test_it_creates_only_one_screen_if_text_does_fit_and_without_screen_or_question_options()
    {
        $expected = $this->shortText;
        $page = new Page($this->shortText, [], []);

        $this->assertEquals($expected, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
    }

    public function test_it_creates_multiple_pages_if_text_does_not_fit_and_without_screen_or_question_options()
    {
        $expectedForFirstPage = "Be aware that not all surveys have all languages. Unfortunately this list isn’t filtered by country. Because of this, it may happen that some...\n[N] Next";
        $page = new Page($this->longText, [], []);

        $this->assertEquals($expectedForFirstPage, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
        $this->assertTrue($page->hasNext());

        $secondPage = $page->getNext();
        $expectedForSecondPage = " languages aren’t available to you. Choose one of the available languages:\n[P] Previous";

        $this->assertEquals($expectedForSecondPage, $secondPage->getText());
        $this->assertPageContentLengthIsCorrect($secondPage->getText());
        $this->assertFalse($secondPage->hasNext());
        $this->assertTrue($secondPage->hasPrevious());
    }

    public function test_it_creates_only_one_screen_if_text_and_screen_options_fit_and_without_question_options()
    {
        $expected = "Choose one of the available languages:\n[C] Cancel";
        $page = new Page($this->shortText, $this->screenOptions, []);

        $this->assertEquals($expected, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
    }

    public function test_it_creates_multiple_pages_if_text_and_screen_options_dont_fit_and_without_question_options()
    {
        $expectedForFirstPage = "Be aware that not all surveys have all languages. Unfortunately this list isn’t filtered by country. Because of this, it may happen that...\n[C] Cancel\n[N] Next";
        $page = new Page($this->longText, $this->screenOptions, []);

        $this->assertEquals($expectedForFirstPage, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
        $this->assertTrue($page->hasNext());

        $secondPage = $page->getNext();
        $expectedForSecondPage = " some languages aren’t available to you. Choose one of the available languages:\n[C] Cancel\n[P] Previous";

        $this->assertEquals($expectedForSecondPage, $secondPage->getText());
        $this->assertPageContentLengthIsCorrect($secondPage->getText());
        $this->assertFalse($secondPage->hasNext());
        $this->assertTrue($secondPage->hasPrevious());
    }

    public function test_it_creates_only_one_screen_if_text_and_question_options_fit_and_without_screen_options()
    {
        $expected = "Choose one of the available languages:\n[1] English\n[2] Spanish\n[3] French";
        $page = new Page($this->shortText, [], $this->questionOptions);

        $this->assertEquals($expected, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
    }

    public function test_it_creates_multiple_pages_if_text_and_question_options_dont_fit_and_without_screen_options()
    {
        $expectedForFirstPage = "Be aware that not all surveys have all languages. Unfortunately this list isn’t filtered by country. Because of this, it may happen that some...\n[N] Next";
        $page = new Page($this->longText, [], $this->questionOptions);

        $this->assertEquals($expectedForFirstPage, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
        $this->assertTrue($page->hasNext());

        $secondPage = $page->getNext();
        $expectedForSecondPage = " languages aren’t available to you. Choose one of the available languages:\n[1] English\n[2] Spanish\n[3] French\n[P] Previous";

        $this->assertEquals($expectedForSecondPage, $secondPage->getText());
        $this->assertPageContentLengthIsCorrect($secondPage->getText());
        $this->assertFalse($secondPage->hasNext());
        $this->assertTrue($secondPage->hasPrevious());
    }

    public function test_it_creates_only_one_screen_if_text_and_screen_and_question_options_fit_all_in_the_same_page()
    {
        $expected = "Choose one of the available languages:\n[1] English\n[2] Spanish\n[3] French\n[C] Cancel";
        $page = new Page($this->shortText, $this->screenOptions, $this->questionOptions);

        $this->assertEquals($expected, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
    }

    public function test_it_creates_multiple_pages_if_text_and_screen_and_question_options_dont_fit_all_in_the_same_page()
    {
        $expectedForFirstPage = "Be aware that not all surveys have all languages. Unfortunately this list isn’t filtered by country. Because of this, it may happen that...\n[C] Cancel\n[N] Next";
        $page = new Page($this->longText, $this->screenOptions, $this->questionOptions);

        $this->assertEquals($expectedForFirstPage, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
        $this->assertTrue($page->hasNext());

        $secondPage = $page->getNext();
        $expectedForSecondPage = " some languages aren’t available to you. Choose one of the available languages:\n[1] English\n[2] Spanish\n[3] French\n[C] Cancel\n[P] Previous";

        $this->assertEquals($expectedForSecondPage, $secondPage->getText());
        $this->assertPageContentLengthIsCorrect($secondPage->getText());
        $this->assertFalse($secondPage->hasNext());
        $this->assertTrue($secondPage->hasPrevious());
    }

    public function test_it_throws_an_exception_if_screen_options_take_all_available_characters()
    {
        $this->expectException(\Exception::class);

        $this->screenOptions[] = new Option('-', str_repeat('-', $this->maxCharactersPerPage + 1));
        new Page($this->shortText, $this->screenOptions, []);
    }

    public function test_it_creates_a_new_page_if_at_least_one_word_of_the_option_label_can_fit_available_characters_count()
    {
        Config::set('ussd.max_characters_per_page', 90);
        $text = 'Which form do you want to complete?';
        $questionOptions = [
            new Option('1', 'Basic Post'),
            new Option('2', 'Location Survey'),
            new Option('3', 'COVID-19'),
        ];
        $expected = "Which form do you want to complete?\n[1] Basic Post\n[2] Location Survey\n[C] Cancel\n[N] Next";
        dump(mb_strlen($expected));

        $page = new Page($text, $this->screenOptions, $questionOptions);

        $this->assertEquals($expected, $page->getText());
        $this->assertPageContentLengthIsCorrect($page->getText());
    }
}
