<?php
namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\ValueObjects\ArticleTitle;
use App\Domain\Articles\ValueObjects\ArticleContent;
use App\Domain\Articles\ValueObjects\ArticleSourceUrl;
use App\Domain\Shared\ValueObjects\Tags;
use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Kanji;
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

    /**
     * Create article from DTO with Value Object validation
     */
    public static function createFromDTO(ArticleCreateDTO $dto, int $userId): self
    {
        // Value Objects handle validation and business rules here
        $titleJp = new ArticleTitle($dto->title_jp);
        $titleEn = $dto->title_en ? new ArticleTitle($dto->title_en) : null;
        $contentJp = new ArticleContent($dto->content_jp);
        $contentEn = $dto->content_en ? new ArticleContent($dto->content_en) : null;
        $sourceUrl = new ArticleSourceUrl($dto->source_link);

        $article = new self();
        $article->title_jp = (string)$titleJp;
        $article->title_en = $titleEn ? (string)$titleEn : null;
        $article->content_jp = (string)$contentJp;
        $article->content_en = $contentEn ? (string)$contentEn : null;
        $article->source_link = (string)$sourceUrl;
        $article->publicity = PublicityStatus::from((int)$dto->publicity);
        $article->user_id = $userId;

        return $article;
    }

    /**
     * Update the article from DTO using Value Objects for validation
     */
    public function updateFromDTO(ArticleUpdateDTO $dto): void
    {
        if ($dto->title_jp !== null) {
            $titleJp = new ArticleTitle($dto->title_jp);
            $this->title_jp = (string)$titleJp;
        }

        if ($dto->title_en !== null) {
            $this->title_en = $dto->title_en ? (string)new ArticleTitle($dto->title_en) : null;
        }

        if ($dto->content_jp !== null) {
            $contentJp = new ArticleContent($dto->content_jp);
            $this->content_jp = (string)$contentJp;
        }

        if ($dto->content_en !== null) {
            $this->content_en = $dto->content_en ? (string)new ArticleContent($dto->content_en) : null;
        }

        if ($dto->source_link !== null) {
            $sourceUrl = new ArticleSourceUrl($dto->source_link);
            $this->source_link = (string)$sourceUrl;
        }

        if ($dto->publicity !== null) {
            $this->publicity = PublicityStatus::from((int)$dto->publicity);
        }

        if ($dto->status !== null) {
            $this->status = ArticleStatus::from((int)$dto->status);
        }
    }

    /**
     * Check if content changes require kanji reprocessing
     */
    public function shouldReprocessContent(ArticleUpdateDTO $dto): bool
    {
        return $dto->reattach || $dto->content_jp !== null;
    }

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
