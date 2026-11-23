<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Http\User;
use App\Infrastructure\Persistence\Models\Concerns\RoutesByUuid;
use Carbon\Carbon;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $title_jp
 * @property string|null $title_en
 * @property string $content_jp
 * @property string|null $content_en
 * @property string $source_link
 * @property PublicityStatus $publicity
 * @property ArticleStatus $status
 * @property int $user_id
 * @property string $uuid
 * @property string|null $entity_type_uuid
 * @property int $n1
 * @property int $n2
 * @property int $n3
 * @property int $n4
 * @property int $n5
 * @property int $uncommon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, Kanji> $kanjis
 * @property-read Collection<int, Word> $words
 */
class Article extends Model
{
    use HasFactory;
    use RoutesByUuid;

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    protected $fillable = [
        'title_jp',
        'title_en',
        'content_jp',
        'content_en',
        'source_link',
        'publicity',
        'status',
        'user_id',
        'uuid',
        'entity_type_uuid',
        'n1',
        'n2',
        'n3',
        'n4',
        'n5',
        'uncommon',
        'content_version',
    ];

    protected $casts = [
        'publicity' => PublicityStatus::class,
        'status' => ArticleStatus::class,
        'n1' => 'integer',
        'n2' => 'integer',
        'n3' => 'integer',
        'n4' => 'integer',
        'n5' => 'integer',
        'uncommon' => 'integer',
        'content_version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'publicity' => PublicityStatus::PRIVATE,
        'status' => ArticleStatus::PENDING,
        'n1' => 0,
        'n2' => 0,
        'n3' => 0,
        'n4' => 0,
        'n5' => 0,
        'uncommon' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kanjis(): BelongsToMany
    {
        return $this->belongsToMany(Kanji::class, 'article_kanji');
    }

    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class, 'article_word');
    }

    /**
     * Adds `processing_status`, the current content-processing status, as a correlated subquery.
     */
    public function scopeWithProcessingStatus(Builder $query): Builder
    {
        return $query->addSelect(['processing_status' => self::currentProcessingState()->select('status')->limit(1)]);
    }

    /**
     * Adds `hashtags_count`, the number of hashtag links, as a correlated subquery.
     */
    public function scopeWithHashtagCount(Builder $query): Builder
    {
        return $query->addSelect(['hashtags_count' => HashtagEntity::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('hashtag_entity.entity_id', 'articles.id')
            ->where('hashtag_entity.entity_type_id', ObjectTemplateType::ARTICLE->getLegacyId())]);
    }

    /**
     * Articles whose current processing row has the given status, or that were never processed
     * when $status is null. Uses the same predicate as withProcessingStatus().
     */
    public function scopeWhereProcessingStatus(Builder $query, ?ProcessingStatus $status): Builder
    {
        return $status === null
            ? $query->whereNotExists(self::currentProcessingState()->toBase())
            : $query->whereExists(self::currentProcessingState()->where('processing_states.status', $status->value)->toBase());
    }

    /**
     * Articles carrying a live hashtag whose text contains $tag, case-insensitively.
     */
    public function scopeTaggedLike(Builder $query, string $tag): Builder
    {
        return $query->whereExists(HashtagEntity::query()
            ->join('uniquehashtags', 'uniquehashtags.id', '=', 'hashtag_entity.hashtag_id')
            ->whereColumn('hashtag_entity.entity_id', 'articles.id')
            ->where('hashtag_entity.entity_type_id', ObjectTemplateType::ARTICLE->getLegacyId())
            ->whereNull('hashtag_entity.deleted_at')
            ->where('uniquehashtags.content', 'ILIKE', '%'.addcslashes(trim($tag), '%_\\').'%')
            ->toBase());
    }

    /**
     * @return Builder<ProcessingState>
     */
    private static function currentProcessingState(): Builder
    {
        return ProcessingState::query()
            ->whereColumn('processing_states.entity_id', 'articles.uuid')
            ->where('processing_states.entity_type', ProcessingEntityType::Article->value)
            ->where('processing_states.task_type', ProcessingTaskType::ArticleContentProcessing->value);
    }
}
