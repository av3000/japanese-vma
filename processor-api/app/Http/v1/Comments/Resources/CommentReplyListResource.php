<?php

declare(strict_types=1);

namespace App\Http\v1\Comments\Resources;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Policies\CommentPolicy;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Models\Comments;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One page of a single comment's subtree.
 *
 * Same envelope as CommentListResource, different item type: a reply carries no
 * replies of its own, so documenting these items as CommentResource would
 * promise a `replies` array that is always empty.
 *
 * @property array{
 *     items: array<int, CommentReplyResource>,
 *     pagination: array{
 *         page: int,
 *         per_page: int,
 *         total: int,
 *         last_page: int,
 *         has_more: bool
 *     }
 * } $resource
 */
class CommentReplyListResource extends JsonResource
{
    public static $wrap = null;

    public static function fromPaginated(
        Comments $replies,
        ?AuthenticatedUser $viewer = null,
        ?CommentPolicy $policy = null,
    ): self {
        $paginator = $replies->getPaginator();
        $resolvedPolicy = $policy ?? new CommentPolicy;

        return new self([
            'items' => array_map(
                static fn (Comment $reply) => new CommentReplyResource($reply, $viewer, $resolvedPolicy),
                $replies->getItems(),
            ),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * @return array{
     *     items: array<int, CommentReplyResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var array<int, CommentReplyResource> */
            'items' => $this->resource['items'],
            'pagination' => new PaginationResource($this->resource['pagination']),
        ];
    }
}
