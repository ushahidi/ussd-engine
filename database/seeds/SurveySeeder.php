<?php

use App\Field;
use App\Survey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $surveysData = json_decode(file_get_contents(base_path('database/seeds/survey.json')));

        foreach ($surveysData as $surveyData) {
            $this->loadSurveyData($surveyData);
        }
    }

    private function loadSurveyData(stdClass $surveyData)
    {
        $survey = new Survey();
        $survey->name = $surveyData->name;
        $survey->description = $surveyData->description;

        $surveyFields = [];
        $fieldsData = array_merge(...Arr::pluck($surveyData->tasks, 'fields'));

        foreach ($fieldsData as $fieldData) {
            $field = $this->loadFieldData($fieldData);
            $surveyFields[] = $field;
        }

        DB::transaction(function () use ($survey, $surveyFields) {
            $survey->save();
            $survey->fields()->saveMany($surveyFields);
        });
    }

    private function loadFieldData(stdClass $fieldData)
    {
        $field = new Field();
        $field->key = $fieldData->key;
        $field->label = $fieldData->label;
        $field->instructions = $fieldData->instructions;
        $field->validation_rules = $this->assembleValidationRules($fieldData);

        return $field;
    }

    private function assembleValidationRules(stdClass $fieldData)
    {
        $rules = [];

        if ($fieldData->required) {
            $rules[] = 'required';
        }

        return implode('|', $rules);
    }
}
