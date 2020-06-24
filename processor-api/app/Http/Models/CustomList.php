<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class CustomList extends Model
{
    protected $table = "customlists";

    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}
