<?php

namespace App\Http\v1\Engagement\Resources;

use App\Domain\Articles\Models\ArticleStats;
use App\Domain\Catalogues\Models\CatalogueStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EngagementStatsSummaryResource extends JsonResource
{
    public function __construct(private readonly ArticleStats|CatalogueStats|null $stats)
    {
        parent::__construct($stats);
    }

    /**
     * @return array{stats: EngagementStatsResource|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => $this->stats ? new EngagementStatsResource($this->stats) : null,
        ];
    }
}
