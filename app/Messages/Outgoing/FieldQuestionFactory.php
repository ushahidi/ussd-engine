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
    public static function create(array $field): FieldQuestion
    {
        switch ($field) {
            case $field['input'] == 'text' && $field['type'] == 'title':
                return new Title($field);
                break;
            case $field['input'] == 'text' && $field['type'] == 'description':
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
