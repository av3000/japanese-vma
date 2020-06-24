<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
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
