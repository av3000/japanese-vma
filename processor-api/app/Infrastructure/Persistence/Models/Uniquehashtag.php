<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $content
 * @property string|null $normalized_content
 */
class Uniquehashtag extends Model
{
    protected $table = 'uniquehashtags';

    /**
     * Writes set `normalized_content` from HashtagName::$key explicitly (HashtagRepository), so the
     * key has one owner. The legacy App\Http\Models\Uniquehashtag keeps a saving hook for the
     * legacy helpers until #149/#152 retire them.
     */
    protected $fillable = ['content', 'normalized_content'];

    /**
     * Keys shared by more than one tag: the case and width variants still waiting to be merged.
     *
     * @return Builder<self>
     */
    public static function duplicateKeys(): Builder
    {
        return self::query()
            ->select('normalized_content')
            ->whereNotNull('normalized_content')
            ->groupBy('normalized_content')
            ->havingRaw('COUNT(*) > 1');
    }

    public function hashtagEntities()
    {
        return $this->hasMany(HashtagEntity::class, 'hashtag_id');
    }
}
