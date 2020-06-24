<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Like;
use App\Http\Models\Download;
use App\Http\Models\View;
use App\Http\Models\Comment;
use App\Http\Models\Post;

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
    public function posts()
    {
        $this->hasMany(Post::class);
    }
}
