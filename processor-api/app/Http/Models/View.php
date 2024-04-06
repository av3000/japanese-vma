<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\ObjectTemplate;
use App\Http\User;

class View extends Model
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
