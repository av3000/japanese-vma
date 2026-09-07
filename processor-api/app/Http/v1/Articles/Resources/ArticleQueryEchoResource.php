<?php

namespace App\Http\v1\Articles\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The canonical echo of what the server understood the request to mean.
 *
 * Clients use it to render "you searched for X" state without re-parsing their own
 * URL, and to confirm that a legacy alias resolved the way they expected. It carries
 * user intent only: mandatory visibility scope is never echoed, because telling a
 * caller which scope was applied tells them something about data they cannot see.
 */
class ArticleQueryEchoResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     q: string|null,
     *     filters: array<string, mixed>,
     *     sort: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'q' => $this->resource['q'] ?? null,
            'filters' => (object) ($this->resource['filters'] ?? []),
            'sort' => $this->resource['sort'] ?? '',
        ];
    }
}
