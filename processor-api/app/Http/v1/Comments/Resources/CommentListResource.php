<?php

namespace App\Http\v1\Comments\Resources;

use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Models\Comments;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
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

    public static function fromPaginated(Comments $comments, bool $includeReplies = false): self
    {
        $paginator = $comments->getPaginator();

        return new self([
            'items' => array_map(
                static fn (Comment $comment) => new CommentResource(
                    comment: $comment,
                    include_replies: $includeReplies,
                ),
                $comments->getItems(),
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
