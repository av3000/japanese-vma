<?php

namespace App\Http\v1\Engagement\Resources;

use App\Domain\Catalogues\Models\CatalogueStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CatalogueStats $resource
 */
class CatalogueDetailEngagementResource extends JsonResource
{
    public function __construct(
        CatalogueStats $stats,
        private readonly bool $isLikedByViewer,
    ) {
        parent::__construct($stats);
    }

    /**
     * @return array{
     *     likes_count: int,
     *     views_count: int,
     *     downloads_count: int,
     *     comments_count: int,
     *     is_liked_by_viewer: bool
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var CatalogueStats $stats */
        $stats = $this->resource;

        return [
            'likes_count' => $stats->getLikesCount(),
            'views_count' => $stats->getViewsCount(),
            'downloads_count' => $stats->getDownloadsCount(),
            'comments_count' => $stats->getCommentsCount(),
            'is_liked_by_viewer' => (bool) $this->isLikedByViewer,
        ];
    }
}
