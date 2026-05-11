<?php

namespace App\Http\v1\Engagement\Resources;

use App\Domain\Engagement\DTOs\EngagementSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property EngagementSummary $resource
 */
class EngagementResource extends JsonResource
{
    public function __construct(EngagementSummary $engagement)
    {
        parent::__construct($engagement);
    }

    /**
     * @return array{
     *     is_liked_by_viewer: bool,
     *     likes_count: int,
     *     views_count: int,
     *     downloads_count: int
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var EngagementSummary $engagement */
        $engagement = $this->resource;

        return [
            'is_liked_by_viewer' => (bool) $engagement->isLikedByViewer,
            'likes_count' => (int) $engagement->likesCount,
            'views_count' => (int) $engagement->viewsCount,
            'downloads_count' => (int) $engagement->downloadsCount,
        ];
    }
}
