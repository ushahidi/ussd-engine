<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    public function answers()
    {
        return $this->hasMany(Answer::class, 'field_id');
    }
}
