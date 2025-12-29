<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Models\Kanji;
use App\Http\Models\Word;
use App\Http\User;

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
        'uncommon'
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
        'uncommon' => 0
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kanjis()
    {
        return $this->belongsToMany(Kanji::class, 'article_kanji');
    }

    public function words()
    {
        return $this->belongsToMany(Word::class, 'article_word');
    }
}
