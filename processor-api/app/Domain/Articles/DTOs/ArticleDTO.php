<?php
namespace App\Domain\Articles\DTOs;

use App\Domain\Shared\ValueObjects\ArticleTitle;
use App\Domain\Shared\ValueObjects\ArticleContent;
use App\Domain\Shared\ValueObjects\SourceUrl;
use App\Domain\Shared\ValueObjects\Tags;
use App\Domain\Shared\ValueObjects\ArticleTimestamp;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\ArticleStatus;

readonly class ArticleDTO
{
    public function __construct(
        public int $id,
        public ArticleTitle $title_jp,
        public ?ArticleTitle $title_en,
        public ArticleContent $content_jp,
        public ?ArticleContent $content_en,
        public SourceUrl $source_link,
        public PublicityStatus $publicity,
        public ArticleStatus $status,
        public array $jlpt_levels,
        public array $stats,
        public AuthorDTO $author,
        public ?Tags $hashtags,
        public ArticleTimestamp $created_at,
        public ArticleTimestamp $updated_at,
        public ?int $jlptcommon = null,
        public array $comments = [],
        public array $kanjis = [],
        public array $words = [],
    ) {}

    public static function fromModel($article): self
    {
        $tagsRaw = '';
        if (!empty($article->hashtags)) {
            // Assuming hashtags contains objects with 'content' property
            $tagsRaw = implode(' ', array_map(fn($tag) => $tag->content ?? '', $article->hashtags));
        }

        return new self(
            id: $article->id,
            title_jp: new ArticleTitle($article->title_jp),
            title_en: $article->title_en ? new ArticleTitle($article->title_en) : null,
            content_jp: new ArticleContent($article->content_jp),
            content_en: $article->content_en ? new ArticleContent($article->content_en) : null,
            source_link: new SourceUrl($article->source_link),
            publicity: $article->publicity,
            status: $article->status,
            jlpt_levels: JlptLevelsDTO::fromModel($article)->toArray(),
            stats: StatsDTO::fromModel($article)->toArray(),
            author: AuthorDTO::fromModel($article->user),
            hashtags: $tagsRaw ? new Tags($tagsRaw) : null,
            created_at: new ArticleTimestamp($article->created_at->toDateTimeString()),
            updated_at: new ArticleTimestamp($article->updated_at->toDateTimeString()),
            jlptcommon: $article->jlptcommon ?? null,
            comments: $article->comments?->toArray() ?? [],
            kanjis: $article->kanjis?->toArray() ?? [],
            words: $article->words?->toArray() ?? [],
        );
    }

    public function toListArray(bool $includeStats = true): array
    {
        $data = [
            'id' => $this->id,
            'title_jp' => $this->title_jp, // Let __toString() handle conversion
            'title_en' => $this->title_en ?? '',
            'content_jp' => $this->content_jp,
            'content_en' => $this->content_en ?? '',
            'source_link' => $this->source_link,
            'publicity' => $this->publicity->value,
            'status' => $this->status->value,
            'jlpt_levels' => $this->jlpt_levels,
            'author' => $this->author->toArray(),
            'hashtags' => $this->hashtags ? $this->hashtags->getTagsArray() : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($includeStats) {
            $data['stats'] = $this->stats;
        }

        return $data;
    }

    public function toDetailArray(): array
    {
        return [
            ...$this->toListArray(),
            'jlptcommon' => $this->jlptcommon,
            'comments' => $this->comments,
            'kanjis' => $this->kanjis,
            'words' => $this->words,
        ];
    }
}
