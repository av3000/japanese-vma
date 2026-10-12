<?php

declare(strict_types=1);

namespace App\Application\Comments\Actions;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;

/**
 * Removes a comment, every reply beneath it and their likes. The author's delete and a
 * moderator's removal both end here. Runs inside the caller's transaction.
 */
class DeleteCommentThreadAction
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
    ) {
    }

    /**
     * @return int Number of replies removed with the comment.
     */
    public function execute(int $commentId): int
    {
        // Deepest replies first, so no row is orphaned mid-transaction.
        $descendantIds = $this->commentRepository->collectDescendantIds($commentId);

        $this->commentRepository->deleteWithLikesByIds([...$descendantIds, $commentId]);

        return count($descendantIds);
    }
}
