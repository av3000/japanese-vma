<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Engagement\ValueObjects\HashtagName;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $content
 * @property string|null $normalized_content
 */
class Uniquehashtag extends Model
{
    protected $table = 'uniquehashtags';

    protected $fillable = ['content'];

    /**
     * The comparison key is derived from the content on every save, so writes that still go
     * through the legacy helpers keep it in step too.
     */
    protected static function booted(): void
    {
        static::saving(static function (Uniquehashtag $tag): void {
            $tag->normalized_content = HashtagName::keyFor((string) $tag->content);
        });
    }

    public function hashtagEntities()
    {
        return $this->hasMany(HashtagEntity::class, 'hashtag_id');
    }
}
