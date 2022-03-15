<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\SelectQuestion;
use Illuminate\Support\Facades\Log;

class SurveyQuestion extends SelectQuestion
{
    /**
     * Construct a language selection question with the provided languages list.
     *
     * @param array $availableSurveys
     */
    public function __construct(array $availableSurveys)
    {
        $field = [
            'label' => __('conversation.selectSurvey'),
            'key' => 'survey',
            'required' => true,
            'options' => $availableSurveys,
        ];
        parent::__construct($field, 'id', 'name');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName(): string
    {
        return 'survey';
    }

    public static function filterEnabledSurveys(array $surveys): array
    {
        $enabledSurveys = config('settings.enabled_surveys', []);

        $enabledSurveysIds = array_map(fn ($survey) => $survey['id'], $enabledSurveys);

        $filteredSurveys = array_filter($surveys, fn ($survey) => in_array($survey['id'], $enabledSurveysIds));

        if (count($enabledSurveys) > 0 && count($filteredSurveys) === 0) {
            Log::warning('Surveys could not be filtered as expected.', ['enabledSurveys' => $enabledSurveys, 'surveysToFilter' => $surveys]);
        }

        return $filteredSurveys;
    }
}
