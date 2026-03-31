<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Models\ArticleStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleStats $resource
 */
class ArticleStatsResource extends JsonResource
{
    public function __construct(ArticleStats $stats)
    {
        parent::__construct($stats);
    }

    public function toArray(Request $request): array
    {
        /** @var ArticleStats $stats */
        $stats = $this->resource;

        return [
            'likes_count' => $stats->getLikesCount(),
            'views_count' => $stats->getViewsCount(),
            'downloads_count' => $stats->getDownloadsCount(),
            'comments_count' => $stats->getCommentsCount(),
        ];
    }
}
