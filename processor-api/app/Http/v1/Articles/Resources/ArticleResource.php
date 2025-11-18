<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\{Article, ArticleStats};

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;


/**
 * @property Article $resource
 */
class ArticleResource extends JsonResource
{
    public function __construct(
        Article $article,
        ?ArticleListDTO $options = null,
        ?ArticleStats $stats = null,
        array $hashtags = [],
        bool $isNew = false
    ) {
        parent::__construct($article);
    }
    /**
     * Transform the article domain model into an API representation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->article->getIdValue(),
            'uuid' => (string) $this->article->getUid(),
            'entity_type_uid' => (string) $this->article->getEntityTypeUid(),
            'title_jp' => (string) $this->article->getTitleJp(),
            'title_en' => (string) $this->article->getTitleEn(),
            'content_preview_jp' => $this->article->getContentJp()->excerpt(),
            'content_preview_en' => $this->article->getContentEn()->excerpt(),
            'source_link' => (string) $this->article->getSourceUrl(),
            'publicity' => $this->article->getPublicity()->value,
            'status' => $this->article->getStatus()->value,
            'jlpt_levels' => $this->article->getJlptLevels()->toArray(),
            'author' => [
                'id' => $this->article->getAuthorId()->value(),
                'name' => $this->article->getAuthorName()->value(),
            ],
            'hashtags' => ($this->options && $this->options->include_hashtags) || $this->isNew ? $this->hashtags : [],
            'created_at' => $this->article->getCreatedAt()->format('c'),
            'updated_at' => $this->article->getUpdatedAt()->format('c'),
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
