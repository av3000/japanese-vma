<?php

namespace App\Http\v1\Comments\Resources;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Policies\CommentPolicy;
use App\Domain\Comments\DTOs\CommentListItemDTO;
use App\Domain\Comments\DTOs\CommentListResultDTO;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One page of top-level comments. `pagination` counts conversations, not rows:
 * replies travel inside their parent's `replies`, never as page entries.
 *
 * @property array{
 *     items: array<int, CommentResource>,
 *     pagination: array{
 *         page: int,
 *         per_page: int,
 *         total: int,
 *         last_page: int,
 *         has_more: bool
 *     }
 * } $resource
 */
class CommentListResource extends JsonResource
{
    public static $wrap = null;

    public static function fromResult(
        CommentListResultDTO $result,
        ?AuthenticatedUser $viewer = null,
        ?CommentPolicy $policy = null,
    ): self {
        $resolvedPolicy = $policy ?? new CommentPolicy;

        return new self([
            'items' => array_map(
                static fn (CommentListItemDTO $item) => new CommentResource(
                    item: $item,
                    viewer: $viewer,
                    policy: $resolvedPolicy,
                ),
                $result->items,
            ),
            'pagination' => $result->pagination->toArray(),
        ]);
    }

    /**
     * @return array{
     *     items: array<int, CommentResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var array<int, CommentResource> */
            'items' => $this->resource['items'],
            'pagination' => new PaginationResource($this->resource['pagination']),
        ];
    }
}
