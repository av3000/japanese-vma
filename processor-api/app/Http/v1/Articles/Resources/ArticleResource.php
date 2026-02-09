<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Models\{Article, ArticleStats};
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

/**
 * @property Article $resource
 */
class ArticleResource extends JsonResource
{
    private ?array $options;
    private ?ArticleStats $stats;
    private array $hashtags;

    public function __construct(
        Article $article,
        ?array $options = null,
        ?ArticleStats $stats = null,
        array $hashtags = [],
        private ?LastOperationState $lastOperation = null
    ) {
        parent::__construct($article);
        $this->options = $options;
        $this->stats = $stats;
        $this->hashtags = $hashtags;
    }
    /**
     * Transform the article domain model into an API representation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var Article $article */
        $article = $this->resource;

        $includeHashtags = $this->options['include_hashtags'] ?? true;
        $includeStats = $this->options['include_stats'] ?? true;

        return [
            'id' => $article->getIdValue(),
            'uuid' => (string) $article->getUid(),
            'entity_type_uid' => (string) $article->getEntityTypeUid(),
            'title_jp' => (string) $article->getTitleJp(),
            'title_en' => (string) $article->getTitleEn(),
            'content_preview_jp' => $article->getContentJp()->excerpt(),
            'content_preview_en' => $article->getContentEn()?->excerpt(),
            'source_link' => (string) $article->getSourceUrl(),
            'publicity' => $article->getPublicity()->value,
            'status' => $article->getStatus()->value,
            'jlpt_levels' => $article->getJlptLevels()->toArray(),
            'author' => [
                'id' => $article->getAuthorId()->value(),
                'name' => $article->getAuthorName()->value(),
            ],
            'hashtags' => $includeHashtags ? $this->hashtags : [],
            'created_at' => $article->getCreatedAt()->format('c'),
            'updated_at' => $article->getUpdatedAt()->format('c'),
            'engagement' => [
                'stats' => $includeStats && $this->stats ? [
                    'likes_count' => $this->stats->getLikesCount(),
                    'views_count' => $this->stats->getViewsCount(),
                    'downloads_count' => $this->stats->getDownloadsCount(),
                    'comments_count' => $this->stats->getCommentsCount(),
                ] : null,
            ],
            'kanjis' => KanjiResource::collection($article->getKanjis()),
            'processing_status' => $this->lastOperation ? [
                'id' => $this->lastOperation->id,
                'type' => $this->lastOperation->task_type,
                'status' => $this->lastOperation->status->value,
                'metadata' => $this->lastOperation->metadata,
                'created_at' => $this->lastOperation->created_at?->toIso8601String(),
                'updated_at' => $this->lastOperation->updated_at?->toIso8601String(),
            ] : null,
        ];
    }
}
