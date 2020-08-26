<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Conversations\SurveyConversation;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class SurveyConversationTest extends TestCase
{
    protected $survey;

    public function setUp()
    {
        parent::setUp();

        $this->survey = [
          'enabled_languages' => [
            'default' => 'en',
            'available' => [],
          ],
        ];
    }

    public function test_it_should_not_ask_for_interaction_language_if_selected_survey_is_available_in_current_locale()
    {
        $conversation = new SurveyConversation();

        // Default language
        App::setLocale('en');
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->shouldAskSurveyLanguage());

        // Available language
        App::setLocale('es');
        $this->survey['enabled_languages']['available'][] = 'es';
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->shouldAskSurveyLanguage());
    }

    public function test_it_should_ask_for_interaction_language_if_selected_survey_is_not_available_in_current_locale()
    {
        $conversation = new SurveyConversation();
        App::setLocale('fr');

        // Language not available
        $this->survey['enabled_languages']['default'] = 'en';
        $this->survey['enabled_languages']['available'] = ['es'];
        $conversation->setSurvey($this->survey);
        $this->assertTrue($conversation->shouldAskSurveyLanguage());
    }

    public function test_it_should_not_ask_for_interaction_language_if_the_survey_does_not_have_languages()
    {
        $conversation = new SurveyConversation();
        App::setLocale('fr');

        // Empty languages
        $this->survey['enabled_languages']['default'] = '';
        $this->survey['enabled_languages']['available'] = [];
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->shouldAskSurveyLanguage());

        $this->survey['enabled_languages']['available'] = [''];
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->shouldAskSurveyLanguage());

        // Without languages
        unset($this->survey['enabled_languages']);
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->shouldAskSurveyLanguage());
    }
}
