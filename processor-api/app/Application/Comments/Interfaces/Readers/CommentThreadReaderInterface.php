<?php

declare(strict_types=1);

namespace App\Application\Comments\Interfaces\Readers;

use App\Domain\Comments\DTOs\CommentPageDTO;
use App\Domain\Comments\DTOs\CommentRepliesPreviewDTO;
use App\Domain\Comments\Queries\CommentQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\Pagination;

/**
 * The read port for comment threads.
 *
 * DatabaseCommentThreadReader is the only implementation today. Anything here
 * must stay expressible by a different store, which is why nothing in this
 * contract may mention Eloquent, a query builder, or a Laravel paginator.
 *
 * Identity reads and writes stay on CommentRepositoryInterface; this port only
 * answers "which comments, in what order, how many".
 */
interface CommentThreadReaderInterface
{
    /**
     * One page of an entity's **top-level** comments.
     *
     * Replies are excluded so that pagination totals count conversations rather
     * than rows, and so a reply can never be paginated away from the comment it
     * answers.
     */
    public function rootPage(
        int $entityId,
        ObjectTemplateType $entityType,
        CommentQueryCriteria $criteria,
        ?int $viewerUserId,
    ): CommentPageDTO;

    /**
     * Subtree sizes and reply previews for a page of top-level comments, in a
     * fixed number of queries regardless of page size or nesting depth.
     *
     * The subtree is returned flat and oldest-first, not as a tree: a reply to a
     * reply belongs to the same conversation, and a bounded flat list is what a
     * two-level thread view renders.
     *
     * `$limitPerRoot` of zero returns counts with no previews. Roots with no
     * replies are absent from the result.
     *
     * @param int[] $rootIds
     *
     * @return array<int, CommentRepliesPreviewDTO> keyed by root comment id
     */
    public function replyPreviews(array $rootIds, int $limitPerRoot, ?int $viewerUserId): array;

    /**
     * One page of a single comment's whole subtree, oldest first.
     *
     * This is what a "show all replies" control reads; the thread endpoints
     * deliberately return only a preview.
     */
    public function replyPage(int $rootId, Pagination $pagination, ?int $viewerUserId): CommentPageDTO;
}
