<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public function objecttemplate() 
    {
        return $this->belongsTo(ObjectTemplate::class);
    }

    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}
