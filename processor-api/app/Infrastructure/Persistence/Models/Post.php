<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Carbon\Carbon;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * v1 Post persistence model.
 *
 * Additive: the legacy read/write paths keep using App\Http\Models\Post.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $entity_type_uuid
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $content
 * @property bool $locked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $author
 */
class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'uuid',
        'entity_type_uuid',
        'user_id',
        'type',
        'title',
        'content',
        'locked',
    ];

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    /**
     * `type` stays a string here because the column stores topic codes as
     * strings; PostMapper casts it to the PostTopic enum.
     */
    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'entity_type_uuid' => 'string',
            'user_id' => 'integer',
            'type' => 'string',
            'title' => 'string',
            'content' => 'string',
            'locked' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
