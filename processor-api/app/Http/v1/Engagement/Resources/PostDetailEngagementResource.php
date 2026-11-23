<?php

declare(strict_types=1);

namespace App\Http\v1\Engagement\Resources;

use App\Domain\Community\Posts\Models\PostStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Post detail engagement: the list's `stats` summary plus whether the viewer
 * has liked the Post, so the like control can load in the right state.
 *
 * @property PostStats|null $resource
 */
class PostDetailEngagementResource extends JsonResource
{
    public function __construct(
        private readonly ?PostStats $stats,
        private readonly bool $isLikedByViewer,
    ) {
        parent::__construct($stats);
    }

    /**
     * @return array{stats: EngagementStatsResource|null, is_liked_by_viewer: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => $this->stats ? new EngagementStatsResource($this->stats) : null,
            'is_liked_by_viewer' => (bool) $this->isLikedByViewer,
        ];
    }
}
