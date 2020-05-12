<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ObjectTemplate extends Model
{
    protected $table = "objecttemplates";

    public function likes()
    {
        $this->hasMany(Like::class);
    }
}
