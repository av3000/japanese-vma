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
    public function downloads()
    {
        $this->hasMany(Download::class);
    }

    public function views()
    {
        $this->hasMany(View::class);
    }

    public function comments()
    {
        $this->hasMany(Comment::class);
    }
}
