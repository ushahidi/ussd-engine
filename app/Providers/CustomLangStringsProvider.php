<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

/**
 * Loads custom language strings. This is used for selectively changing the messages offered to users.
 * The strings are obtained from a JSON file at the root of the project named lang_strings.json
 * 
 * Expected format is:
 * 
 *   {
 *     "en": {
 *       "conversation.selectSurvey": "Choose your adventure"
 *     },
 *     "es": {
 *       "conversation.selectSurvey": "Elija su aventura"
 *     }
 *   }
 */
class CustomLangStringsProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (file_exists(base_path('lang_strings.json'))) {
            $lang_strings = json_decode(file_get_contents(base_path('lang_strings.json')), true);

            if (is_null($lang_strings)) {
                // Parsing error
                Log::warning('Error loading custom language strings', ['parser_error' => json_last_error_msg()]);
                return;
            }

            foreach ($lang_strings as $locale => $lines) {
                // We need to pre-load the affected locales+groups, because otherwise the Translator
                // may consider the groups already loaded and not look into the main files.
                // This would have the effect of hiding strings that are NOT provided in the custom files.
                $this->preloadForLines($locale, $lines);

                // Add custom lines from file
                app('translator')->addLines($lines, $locale);
            }
        }
    }

    private function preloadForLines($locale, $lines)
    {
        $keys = array_keys($lines);
        $groups = array_unique(array_map(fn($x) => explode('.', $x)[0], $keys));
        foreach ($groups as $group) {
            app('translator')->load('*', $group, $locale);
        }
    }
}
