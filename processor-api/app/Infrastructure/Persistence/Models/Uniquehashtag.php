<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Engagement\ValueObjects\HashtagName;
use App\Domain\Shared\Enums\ObjectTemplateType;
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

    /**
     * Usage figures for a page of tags in one query: a grouped subquery over hashtag_entity joined
     * as `usage`, plus a correlated count of the tag's case and width variants. Adds
     * active_links, removed_links, first_used_at, last_used_at, variant_count and one
     * usageColumn() per type in $types. The usage filters below read the same `usage` alias, so
     * apply this scope first.
     *
     * @param list<ObjectTemplateType> $types
     */
    public function scopeWithUsage(Builder $query, array $types): Builder
    {
        // withTrashed: removed links are counted too, as removed_links.
        $usage = HashtagEntity::withTrashed()
            ->toBase()
            ->select('hashtag_id')
            ->selectRaw('COUNT(*) FILTER (WHERE deleted_at IS NULL) AS active_links')
            ->selectRaw('COUNT(*) FILTER (WHERE deleted_at IS NOT NULL) AS removed_links')
            ->selectRaw('MIN(created_at) AS first_used_at')
            ->selectRaw('MAX(created_at) AS last_used_at')
            ->groupBy('hashtag_id');

        foreach ($types as $type) {
            $usage->selectRaw('COUNT(*) FILTER (WHERE deleted_at IS NULL AND entity_type_id = ?) AS '.self::usageColumn($type), [$type->getLegacyId()]);
        }

        $query
            ->leftJoinSub($usage, 'usage', 'usage.hashtag_id', '=', 'uniquehashtags.id')
            ->select('uniquehashtags.*')
            ->selectRaw('COALESCE(usage.active_links, 0) AS active_links')
            ->selectRaw('COALESCE(usage.removed_links, 0) AS removed_links')
            ->selectRaw('usage.first_used_at, usage.last_used_at')
            ->selectRaw('(SELECT COUNT(*) FROM uniquehashtags AS variants WHERE variants.normalized_content = uniquehashtags.normalized_content) AS variant_count');

        foreach ($types as $type) {
            $column = self::usageColumn($type);
            $query->selectRaw("COALESCE(usage.{$column}, 0) AS {$column}");
        }

        return $query;
    }

    /**
     * Tags with at least one live link on the given entity type. Needs withUsage().
     */
    public function scopeUsedOn(Builder $query, ObjectTemplateType $type): Builder
    {
        return $query->whereRaw('COALESCE(usage.'.self::usageColumn($type).', 0) > 0');
    }

    /**
     * Tags with no live link. Needs withUsage().
     */
    public function scopeUnused(Builder $query): Builder
    {
        return $query->whereRaw('COALESCE(usage.active_links, 0) = 0');
    }

    /**
     * Tags with at least $minimum live links. Needs withUsage().
     */
    public function scopeUsedAtLeast(Builder $query, int $minimum): Builder
    {
        return $query->whereRaw('COALESCE(usage.active_links, 0) >= ?', [$minimum]);
    }

    /**
     * Tags whose text contains $search, as typed or once case and width are ignored.
     */
    public function scopeMatching(Builder $query, string $search): Builder
    {
        return $query->where(static fn (Builder $inner) => $inner
            ->where('uniquehashtags.content', 'ILIKE', '%'.addcslashes($search, '%_\\').'%')
            ->orWhere('uniquehashtags.normalized_content', 'LIKE', '%'.addcslashes(HashtagName::keyFor($search), '%_\\').'%'));
    }

    /**
     * The usage column for one entity type, such as `post_links`. Built from the enum case, never
     * from input, so it is safe to interpolate.
     */
    public static function usageColumn(ObjectTemplateType $type): string
    {
        return mb_strtolower($type->name).'_links';
    }

    public function hashtagEntities()
    {
        return $this->hasMany(HashtagEntity::class, 'hashtag_id');
    }
}
