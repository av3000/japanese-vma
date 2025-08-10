<?php

namespace App\Domain\Articles\Models;

use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Kanji;
use App\Http\Models\Word;
use App\Http\User;

/**
 * Article Model
 *
 * @property int $id
 * @property string $title_jp
 * @property string|null $title_en
 * @property string $content_jp
 * @property string|null $content_en
 * @property string $source_link
 * @property bool $publicity
 * @property int $status
 * @property int $user_id
 * @property int $n1
 * @property int $n2
 * @property int $n3
 * @property int $n4
 * @property int $n5
 * @property int $uncommon
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Article extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title_jp',
        'title_en',
        'content_jp',
        'content_en',
        'source_link',
        'publicity',
        'status',
        'user_id',
        'n1',
        'n2',
        'n3',
        'n4',
        'n5',
        'uncommon'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
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

    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'publicity' => PublicityStatus::PRIVATE,
        'status' => ArticleSTatus::PENDING,
        'n1' => 0,
        'n2' => 0,
        'n3' => 0,
        'n4' => 0,
        'n5' => 0,
        'uncommon' => 0
    ];

    /**
     * Get the user that owns the article.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the kanjis for the article.
     */
    public function kanjis()
    {
        return $this->belongsToMany(Kanji::class, 'article_kanji');
    }

    /**
     * Get the words for the article.
     */
    public function words()
    {
        return $this->belongsToMany(Word::class, 'article_word');
    }
}
