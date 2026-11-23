<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Resources;

use App\Domain\Community\Posts\DTOs\PostListItemDTO;
use App\Domain\Community\Posts\DTOs\PostListResultDTO;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read PostListResultDTO $resource
 */
class PostListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(PostListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, PostListItemResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var PostListResultDTO $result */
        $result = $this->resource;

        /** @var array<int, PostListItemResource> $items */
        $items = array_map(
            static fn (PostListItemDTO $item): PostListItemResource => new PostListItemResource(
                $item->post,
                $item->stats,
                $item->hashtags,
            ),
            $result->items,
        );

        return [
            /** @var array<int, PostListItemResource> */
            'items' => $items,
            'pagination' => new PaginationResource($result->pagination),
        ];
    }
}
