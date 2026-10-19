<?php

namespace App\Infrastructure\Persistence\Models;

use App\Http\Models\Like;
use App\Http\Models\ObjectTemplate;
use App\Http\User;
use App\Infrastructure\Persistence\Models\Concerns\RoutesByUuid;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use RoutesByUuid;

    protected $fillable = [
        'template_id',
        'real_object_id',
        'user_id',
        'parent_comment_id',
        'content',
        'uuid',
        'real_object_uuid',
        'entity_type_uuid',
    ];

    protected $casts = [
        'template_id' => 'integer',
        'real_object_id' => 'integer',
        'user_id' => 'integer',
        'parent_comment_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scope for finding comments by entity
    public function scopeForEntity($query, int $templateId, int $objectId)
    {
        return $query->where('template_id', $templateId)
            ->where('real_object_id', $objectId);
    }

    public function objecttemplate()
    {
        return $this->belongsTo(ObjectTemplate::class, 'template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'real_object_id')
            ->where('template_id', function ($query) {
                $query->select('id')
                    ->from('objecttemplates')
                    ->where('title', 'comment');
            });
    }

    public function parentComment()
    {
        return $this->belongsTo(Comment::class, 'parent_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_comment_id');
    }
}
