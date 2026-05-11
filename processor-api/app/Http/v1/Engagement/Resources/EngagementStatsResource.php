<?php

namespace App\Http\v1\Engagement\Resources;

use App\Domain\Articles\Models\ArticleStats;
use App\Domain\Catalogues\Models\CatalogueStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleStats|CatalogueStats $resource
 */
class EngagementStatsResource extends JsonResource
{
    public function __construct(ArticleStats|CatalogueStats $stats)
    {
        parent::__construct($stats);
    }

    /**
     * @return array{
     *     likes_count: int,
     *     views_count: int,
     *     downloads_count: int,
     *     comments_count: int
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var ArticleStats|CatalogueStats $stats */
        $stats = $this->resource;

        return [
            'likes_count' => $stats->getLikesCount(),
            'views_count' => $stats->getViewsCount(),
            'downloads_count' => $stats->getDownloadsCount(),
            'comments_count' => $stats->getCommentsCount(),
        ];
    }
}
