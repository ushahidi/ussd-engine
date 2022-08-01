<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\Fields\Categories;
use App\Messages\Outgoing\Fields\Checkboxes;
use App\Messages\Outgoing\Fields\Date;
use App\Messages\Outgoing\Fields\DateTime;
use App\Messages\Outgoing\Fields\Decimal;
use App\Messages\Outgoing\Fields\Description;
use App\Messages\Outgoing\Fields\Image;
use App\Messages\Outgoing\Fields\Integer;
use App\Messages\Outgoing\Fields\LongText;
use App\Messages\Outgoing\Fields\Markdown;
use App\Messages\Outgoing\Fields\RadioButtons;
use App\Messages\Outgoing\Fields\Select;
use App\Messages\Outgoing\Fields\ShortText;
use App\Messages\Outgoing\Fields\Title;
use App\Messages\Outgoing\Fields\Video;
use App\Messages\Outgoing\GeoLocation;

class FieldQuestionFactory
{

    protected static function isTitleField(array $field): bool
    {
        return $field['input'] == 'text' && $field['type'] == 'title';
    }

    protected static function isDescriptionField(array $field): bool
    {
        return $field['input'] == 'text' && $field['type'] == 'description';
    }

    protected static function getFieldDefault(array $field)
    {
        if (!$field['default']) {
            return null;
        }

        if (self::isTitleField($field)) {
            $setting = config('settings.when_default_values.title');
        } elseif (self::isDescriptionField($field)) {
            $setting = config('settings.when_default_values.description');
        } else {
            /* TODO: handling of default values for other fields disabled.
             *       Before enabling, it's necessary to check that this 
             *       works well with data types other than text.
             */
            // $setting = config('settings.when_default_values.other');
            $setting = 'ignore';
        }

        if ($setting == 'ignore') {
            return null;
        }

        return [
            'value' => $field['default'],
            'setting' => $setting       # 'skip' or 'use'
        ];
    }

    public static function create(array $field, string $driverFormat = null, string $driverProtocol = null): FieldQuestion
    {
        $defaultsSetting = self::getFieldDefault($field);

        $field = self::createField($field);
        $field->setDriverInfo($driverFormat, $driverProtocol);

        if ($defaultsSetting && $defaultsSetting['value']) {
            $field->setDefaultAnswerValue($defaultsSetting['value']);

            if ($defaultsSetting['setting'] == 'skip') {
                $field->setSkipQuestion(true);
            }
        }

        return $field;
    }   

    protected static function createField(array $field): FieldQuestion
    {
        switch ($field) {
            case self::isTitleField($field):
                return new Title($field);
                break;
            case self::isDescriptionField($field):
                return new Description($field);
                break;
            case $field['input'] == 'text' && $field['type'] == 'varchar':
                return new ShortText($field);
                break;
            case $field['input'] == 'textarea' && $field['type'] == 'text':
                return new LongText($field);
                break;
            case $field['input'] == 'number' && $field['type'] == 'int':
                return new Integer($field);
                break;
            case $field['input'] == 'number' && $field['type'] == 'decimal':
                return new Decimal($field);
                break;
            case $field['input'] == 'location' && $field['type'] == 'point':
                return new GeoLocation($field);
                break;
            case $field['input'] == 'date' && $field['type'] == 'datetime':
                return new Date($field);
                break;
            case $field['input'] == 'datetime' && $field['type'] == 'datetime':
                return new DateTime($field);
                break;
            case $field['input'] == 'radio' && $field['type'] == 'varchar':
                return new RadioButtons($field);
                break;
            case $field['input'] == 'checkbox' && $field['type'] == 'varchar':
                return new Checkboxes($field);
                break;
            case $field['input'] == 'select' && $field['type'] == 'varchar':
                return new Select($field);
                break;
            case $field['input'] == 'markdown' && $field['type'] == 'markdown':
                return new Markdown($field);
                break;
            case $field['input'] == 'tags' && $field['type'] == 'tags':
                return new Categories($field);
                break;
            case $field['input'] == 'upload' && $field['type'] == 'media`':
                return new Image($field);
                break;
            case $field['input'] == 'video' && $field['type'] == 'varchar`':
                return new Video($field);
                break;
            default:
                return new TextQuestion($field);
                break;
        }
    }

}
