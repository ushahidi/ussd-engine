<?php

$default_settings = [
    # This acts as an intersection filter with the languages enabled
    # in the connected deployment. If left empty, no filtering is applied
    # Specify langauges as configured in Platform surveys, i.e. [ 'en-US', 'es-ES' ]
    "enabled_languages" => [],

    # This acts as an intersection filter with the surveys present in the
    # connected deployment. If left empty, no filtering is applied.
    # At the moment, survey selection can be done only by id, i.e.:
    #   [{"id": 1}, {"id": 2}]  --> enables surveys with id=1 and id=2 only
    "enabled_surveys" => [],
    
    # This specifies what behavior to apply for default values defined 
    # in survey fields. Three possible behaviours are available:
    #   'ignore' : don't use the default values
    #   'use'    : use as default value in the USSD interaction
    #   'skip'   : use the default value as response and don't present the
    #              question to the end-user
    "when_default_values" => [
        "title" => "ignore",
        "description" => "ignore",
        "other" => "ignore"   # TODO: not implemented for now, see FieldQuestionFactory class to enable
    ],
];

if (file_exists(base_path('settings.json'))) {
    $settings = json_decode(file_get_contents(base_path('settings.json')), true);

    # Merge with defaults
    $settings = array_replace_recursive($default_settings, $settings);
}

return $settings ?? $default_settings;
