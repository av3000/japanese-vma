<?php

declare(strict_types=1);

namespace App\Application\Comments\Actions;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Comments\DTOs\CommentSnapshotDTO;

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
     * @return list<CommentSnapshotDTO> the replies removed with the comment, read before deleting
     */
    public function execute(int $commentId): array
    {
        // Deepest replies first, so no row is orphaned mid-transaction.
        $descendantIds = $this->commentRepository->collectDescendantIds($commentId);
        $replies = $this->commentRepository->findSnapshotsByIds($descendantIds);

        $this->commentRepository->deleteWithLikesByIds([...$descendantIds, $commentId]);

        return $replies;
    }
}
