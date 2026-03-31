<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Models\ArticleStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleListEngagementResource extends JsonResource
{
    public function __construct(private readonly ?ArticleStats $stats)
    {
        parent::__construct($stats);
    }

    public function toArray(Request $request): array
    {
        return [
            'stats' => $this->stats ? new ArticleStatsResource($this->stats) : null,
        ];
    }
}
