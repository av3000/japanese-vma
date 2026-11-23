<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Http\Models\ObjectTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'template_id',
        'real_object_id',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'template_id' => 'integer',
        'real_object_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * A correlated COUNT(*) of the likes on the owning row, for `addSelect(['likes_count' => ...])`
     * on a list query, so a page of rows costs one query.
     *
     * @param string $ownerColumn qualified id column of the outer query, such as `posts.id`
     *
     * @return Builder<self>
     */
    public static function countSubquery(ObjectTemplateType $type, string $ownerColumn): Builder
    {
        return self::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('likes.real_object_id', $ownerColumn)
            ->where('likes.template_id', $type->getLegacyId());
    }

    public function objecttemplate()
    {
        return $this->belongsTo(ObjectTemplate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
