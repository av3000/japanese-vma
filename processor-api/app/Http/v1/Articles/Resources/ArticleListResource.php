<?php

namespace App\Http\v1\Articles\Resources;

use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *     items: array<int, ArticleResource>,
 *     pagination: array{
 *         page: int,
 *         per_page: int,
 *         total: int,
 *         last_page: int,
 *         has_more: bool
 *     }
 * } $resource
 */
class ArticleListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     items: array<int, ArticleResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var array<int, ArticleResource> */
            'items' => $this->resource['items'],
            'pagination' => new PaginationResource($this->resource['pagination']),
        ];
    }
}
