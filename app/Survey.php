<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    public function fields()
    {
        return $this->hasMany(Field::class, 'survey_id');
    }
}
