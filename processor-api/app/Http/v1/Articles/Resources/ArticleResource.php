<?php

namespace App\Http\v1\Articles\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Articles\Models\Article;

class ArticleResource extends JsonResource
{
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
            'uid' => (string) $this->resource->getUid(),
            'title_jp' => (string) $this->resource->getTitleJp(),
            'title_en' => (string) $this->resource->getTitleEn(),
            'content_preview' => $this->resource->getContentJp()->excerpt(),
            'source_link' => (string) $this->resource->getSourceUrl(),
            'publicity' => $this->resource->getPublicity()->value,
            'status' => $this->resource->getStatus()->value,
            'jlpt_levels' => $this->resource->getJlptLevels()->toArray(),
            'author' => [
                'id' => $this->resource->getAuthorId()->value(),
                'name' => $this->resource->getAuthorName()->value(),
            ],
            'hashtags' => $this->when($this->include_hashtags, [$this->resource->getTags()]),
            'created_at' => $this->resource->getCreatedAt()->format('c'),
            'updated_at' => $this->resource->getUpdatedAt()->format('c'),
            'engagement' => $this->when(
                $this->include_stats,
                [
                    'likes_count' => $this->resource->getLikesCount(),
                    'downloads_count' => $this->resource->getDownloadsCount(),
                    'views_count' => $this->resource->getViewsCount(),
                    'comments_count' => $this->resource->getCommentsCount(),
                ]
            ),
        ];
    }
}
