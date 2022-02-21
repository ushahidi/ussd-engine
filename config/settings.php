<?php

if (file_exists(base_path('settings.json'))) {
    $settings = json_decode(file_get_contents(base_path('settings.json')), true);
}

return $settings ?? [];
