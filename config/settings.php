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

    # Send question for survey selection confirmation
    "confirm_survey_selection" => true,

    # USSD-specific settings
    "ussd" => [
        # Should we show hints for specific field types.
        # The keys for this array match the values returned by the
        # getAtributeName() method in the different App\Messages\Outgoing\Fields questions
        "show_hints_for_field_type" => [
            "categories" => false,
            "checkboxes" => true,
            "date" => true,
            "datetime" => true,
            "decimal" => false,
            "description" => false,
            "image" => false,
            "integer" => false,
            "location" => true,
            "long text" => false,
            "markdown" => false,
            "radio buttons" => false,
            "select" => false,
            "short text" => false,
            "title" => false,
            "video" => false,
        ],

        # Disable field types for user input via USSD?
        "is_disabled_field_type" => [
            "image" => true,
            "location" => true,
            "video" => true,
        ],
    ],
];

if (file_exists(base_path('settings.json'))) {
    $settings = json_decode(file_get_contents(base_path('settings.json')), true);

    # Merge with defaults
    $settings = array_replace_recursive($default_settings, $settings);
}

return $settings ?? $default_settings;
