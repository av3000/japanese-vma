<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Queries\ArticleQueryCriteria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The canonical echo of what the server understood the request to mean.
 *
 * Clients use it to render "you searched for X" state without re-parsing their own
 * URL. It carries user intent only: the mandatory visibility scope is never echoed,
 * because telling a caller which scope was applied tells them something about data
 * they cannot see.
 *
 * @property ArticleQueryCriteria $resource
 */
class ArticleAppliedCriteriaResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     q: string|null,
     *     filters: ArticleAppliedFiltersResource,
     *     sort: string
     * }
     */
    public function toArray(Request $request): array
    {
        $criteria = $this->resource;

        return [
            'q' => $criteria->hasSearch() ? $criteria->search->value : null,
            'filters' => new ArticleAppliedFiltersResource($criteria),
            'sort' => $criteria->sort->toSigned(),
        ];
    }
}
