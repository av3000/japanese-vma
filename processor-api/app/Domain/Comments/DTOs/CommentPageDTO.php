<?php

declare(strict_types=1);

namespace App\Domain\Comments\DTOs;

use App\Domain\Comments\Models\Comment;

/**
 * What a reader returns: the Comments for one page, plus page metadata.
 *
 * Enrichment is deliberately absent. Readers resolve ordering and pagination;
 * CommentService attaches subtree sizes and reply previews, so a future reader
 * over different storage does not have to reimplement that batching.
 */
final readonly class CommentPageDTO
{
    /**
     * @param array<int, Comment> $comments
     */
    public function __construct(
        public array $comments,
        public CommentPaginationDTO $pagination,
    ) {
    }
}
