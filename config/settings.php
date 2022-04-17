<?php

$default_settings = [
    "enabled_langauges" => [],
    "enabled_surveys" => [],
    "when_default_values" => [      # 'skip', 'ignore' or 'use'
        "title" => "ignore",
        "description" => "ignore",
        "other" => "ignore"         # TODO: this is ignored for now, see FieldQuestionFactory class to enable
    ]
];

if (file_exists(base_path('settings.json'))) {
    $settings = json_decode(file_get_contents(base_path('settings.json')), true);

    # Merge with defaults
    $settings = array_merge($default_settings, $settings);
    $settings['when_default_values'] = array_merge($default_settings['when_default_values'], $settings['when_default_values']);
}

return $settings ?? $default_settings;
