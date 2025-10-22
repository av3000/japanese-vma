<?php

namespace App\Http\v1\Articles\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\{Article, ArticleStats};

class ArticleResource extends JsonResource
{
    private ArticleListDTO $options;

    public function __construct($article, ArticleListDTO $options, ?ArticleStats $stats = null, ?EngagementData $engagement = null, array $hashtags = [])
    {
        parent::__construct($article);
        $this->options = $options;
        $this->stats = $stats;
        $this->engagement = $engagement;
        $this->hashtags = $hashtags;
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
            'hashtags' => $this->when($this->options->include_hashtags, $this->hashtags),
            'created_at' => $this->resource->getCreatedAt()->format('c'),
            'updated_at' => $this->resource->getUpdatedAt()->format('c'),
            'engagement' => [
                'stats' => $this->when(
                    $this->stats !== null,
                    [
                        'likes_count' => $this->stats?->getLikesCount(),
                        'views_count' => $this->stats?->getViewsCount(),
                        'downloads_count' => $this->stats?->getDownloadsCount(),
                        'comments_count' => $this->stats?->getCommentsCount(),
                    ]
                ),
                'data' => $this->when(
                    $this->engagement !== null,
                    [
                        'likes' => $this->engagement?->getLikes(),
                        'views' => $this->engagement?->getViews(),
                        'downloads' => $this->engagement?->getDownloads(),
                        'comments' => $this->engagement?->getComments(),
                    ]
                )
            ]
            // 'engagement' => [
            //     'engagement_counts' => $this->when(
            //         $this->options->include_stats_counts,
            //         [
            //             'likes_count' => $this->resource->getLikesCount(),
            //             'downloads_count' => $this->resource->getDownloadsCount(),
            //             'views_count' => $this->resource->getViewsCount(),
            //             'comments_count' => $this->resource->getCommentsCount(),
            //         ]
            //     ),
            //     'engagement_data' => $this->when(
            //         $this->resource->getEngagementData() !== null,
            //         function() {
            //             $engagement = $this->resource->getEngagementData();
            //             return [
            //                 'likes' => $engagement?->getLikes(),
            //                 'views' => $engagement?->getViews(),
            //                 'downloads' => $engagement?->getDownloads(),
            //                 'comments' => $engagement?->getComments(),
            //             ];
            //         }
            //     )
            // ],
        ];
    }
}
