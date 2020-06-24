<?php

namespace App\Http\Models;
use App\Http\Models\ObjectTemplate;
use App\User;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
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
