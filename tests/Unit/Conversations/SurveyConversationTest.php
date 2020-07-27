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

    public function test_it_returns_true_if_selected_survey_is_available_in_current_locale()
    {
        $conversation = new SurveyConversation();

        // Default language
        App::setLocale('en');
        $conversation->setSurvey($this->survey);
        $this->assertTrue($conversation->isSurveyAvailableInCurrentLocale());

        // Available language
        App::setLocale('es');
        $this->survey['enabled_languages']['available'][] = 'es';
        $conversation->setSurvey($this->survey);
        $this->assertTrue($conversation->isSurveyAvailableInCurrentLocale());
    }

    public function test_it_returns_false_if_selected_survey_is_not_available_in_current_locale()
    {
        $conversation = new SurveyConversation();
        App::setLocale('fr');

        // Default language
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->isSurveyAvailableInCurrentLocale());

        // Available language
        $this->survey['enabled_languages']['available'][] = [];
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->isSurveyAvailableInCurrentLocale());

        // Without languages
        unset($this->survey['enabled_languages']);
        $conversation->setSurvey($this->survey);
        $this->assertFalse($conversation->isSurveyAvailableInCurrentLocale());
    }
}
