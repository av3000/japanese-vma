<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Engagement\DTOs\EngagementSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property EngagementSummary $resource
 */
class ArticleDetailEngagementResource extends JsonResource
{
    public function __construct(EngagementSummary $engagement)
    {
        parent::__construct($engagement);
    }

    public function toArray(Request $request): array
    {
        /** @var EngagementSummary $engagement */
        $engagement = $this->resource;

        return [
            'is_liked_by_viewer' => $engagement->isLikedByViewer,
            'likes_count' => $engagement->likesCount,
            'views_count' => $engagement->viewsCount,
            'downloads_count' => $engagement->downloadsCount,
        ];
    }
}
