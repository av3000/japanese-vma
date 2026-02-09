<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\User;
use App\Http\Models\ObjectTemplate;
use App\Http\Models\Like;

class Comment extends Model
{
    protected $fillable = [
        'template_id',
        'real_object_id',
        'user_id',
        'parent_comment_id',
        'content',
        'uuid',
        'real_object_uuid',
        'entity_type_uuid'
    ];
    public function objecttemplate()
    {
        return $this->belongsTo(ObjectTemplate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
