<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Http\Models\Word;
use App\Http\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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
}
