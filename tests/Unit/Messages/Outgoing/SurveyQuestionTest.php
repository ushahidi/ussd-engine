<?php

namespace Tests\Unit\Messages\Outgoing;

use App\Messages\Outgoing\SurveyQuestion;
use Tests\TestCase;

class SurveyQuestionTest extends TestCase
{
    public function test_it_filters_surveys_by_enabled_surveys_setting()
    {
        $enabledSurveys = [
            [
                'id' => 1
            ],
            [
                'id' => 3
            ]
        ];
        config(['settings.enabled_surveys' => $enabledSurveys]);

        $surveysToFilter = [
            [
                'id' => 1,
                'name' => 'Basic Survey'
            ],
            [
                'id' => 2,
                'name' => 'Complex Survey'
            ],
            [
                'id' => 4,
                'name' => 'Advanced Survey'
            ]
        ];
        $expectedFilteredSurveys = [
            [
                'id' => 1,
                'name' => 'Basic Survey'
            ]
        ];

        $actualFilteredSurveys = SurveyQuestion::filterEnabledSurveys($surveysToFilter);

        $this->assertEquals($expectedFilteredSurveys, $actualFilteredSurveys);
    }
}
