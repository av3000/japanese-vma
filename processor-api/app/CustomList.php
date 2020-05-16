<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomList extends Model
{
    protected $table = "customlists";

    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}
