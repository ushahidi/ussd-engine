<?php

namespace App;

use stdClass;

class Surveys
{
    public static function load(string $path = null)
    {
        if (! $path) {
            $path = base_path('app/surveys.json');
        }

        $surveys = json_decode(file_get_contents($path));

        return $surveys;
    }

    public static function assembleFieldValidationRules(stdClass $field)
    {
        $rules = [];

        if ($field->required) {
            $rules[] = 'required';
        }

        return implode('|', $rules);
    }
}
