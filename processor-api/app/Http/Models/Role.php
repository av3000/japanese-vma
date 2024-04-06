<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\User;

class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, "user_role");
    }
}
