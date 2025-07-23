<?php
namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\DTOs\JlptLevelsDTO;
use App\Domain\Articles\DTOs\StatsDTO;
use App\Domain\Articles\DTOs\AuthorDTO;

readonly class ArticleDTO
{
    public function __construct(
        public int $id,
        public string $title_jp,
        public string $title_en,
        public string $content_jp,
        public string $content_en,
        public ?string $source_link,
        public string $publicity,
        public string $status,
        public array $jlpt_levels,
        public array $stats,
        public AuthorDTO $author,
        public array $hashtags,
        public string $created_at,
        public string $updated_at,
        public ?int $jlptcommon = null,
        public array $comments = [],
        public array $kanjis = [],
        public array $words = [],
    ) {}

    public static function fromModel($article): self
    {
        return new self(
            id: $article->id,
            title_jp: $article->title_jp,
            title_en: $article->title_en,
            content_jp: $article->content_jp,
            content_en: $article->content_en,
            source_link: $article->source_link,
            publicity: $article->publicity,
            status: $article->status,
            jlpt_levels: JlptLevelsDTO::fromModel($article)->toArray(),
            stats: StatsDTO::fromModel($article)->toArray(),
            author: AuthorDTO::fromModel($article->user),
            hashtags: $article->hashtags ?? [],
            created_at: $article->created_at->toDateTimeString(),
            updated_at: $article->updated_at->toDateTimeString(),
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
            'title_jp' => $this->title_jp,
            'title_en' => $this->title_en,
            'content_jp' => $this->content_jp,
            'content_en' => $this->content_en,
            'source_link' => $this->source_link,
            'publicity' => $this->publicity,
            'status' => $this->status,
            'jlpt_levels' => $this->jlpt_levels,
            'author' => $this->author->toArray(),
            'hashtags' => $this->hashtags,
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
