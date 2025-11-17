<?php

namespace App\Http\v1\Articles\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\{Article, ArticleStats};

class ArticleResource extends JsonResource
{
    private ?ArticleListDTO $options;

    public function __construct(
        $article,
        ?ArticleListDTO $options = null,
        ?ArticleStats $stats = null,
        array $hashtags = [],
        bool $isNew = false
    ) {
        parent::__construct($article);
        $this->options = $options;
        $this->stats = $stats;
        $this->hashtags = $hashtags;
        $this->isNew = $isNew;
    }
    /**
     * Transform the article domain model into an API representation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Article $this->resource */
        return [
            'id' => $this->resource->getIdValue(),
            'uuid' => (string) $this->resource->getUid(),
            'entity_type_uid' => (string) $this->resource->getEntityTypeUid(),
            'title_jp' => (string) $this->resource->getTitleJp(),
            'title_en' => (string) $this->resource->getTitleEn(),
            'content_preview_jp' => $this->resource->getContentJp()->excerpt(),
            'content_preview_en' => $this->resource->getContentEn()->excerpt(),
            'source_link' => (string) $this->resource->getSourceUrl(),
            'publicity' => $this->resource->getPublicity()->value,
            'status' => $this->resource->getStatus()->value,
            'jlpt_levels' => $this->resource->getJlptLevels()->toArray(),
            'author' => [
                'id' => $this->resource->getAuthorId()->value(),
                'name' => $this->resource->getAuthorName()->value(),
            ],
            'hashtags' => ($this->options && $this->options->include_hashtags) || $this->isNew ? $this->hashtags : [],
            'created_at' => $this->resource->getCreatedAt()->format('c'),
            'updated_at' => $this->resource->getUpdatedAt()->format('c'),
            'engagement' => [
                'stats' => $this->stats ? [
                        'likes_count' => $this->stats?->getLikesCount(),
                        'views_count' => $this->stats?->getViewsCount(),
                        'downloads_count' => $this->stats?->getDownloadsCount(),
                        'comments_count' => $this->stats?->getCommentsCount(),
                ] : null,
            ]
        ];
    }
}
