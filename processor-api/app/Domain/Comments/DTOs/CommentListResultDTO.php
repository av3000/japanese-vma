<?php

declare(strict_types=1);

namespace App\Domain\Comments\DTOs;

/**
 * A page of top-level comments with their thread metadata, ready for a resource.
 *
 * CommentPageDTO is the raw reader output; this is what the list use case
 * returns after attaching subtree sizes and reply previews to it.
 */
final readonly class CommentListResultDTO
{
    /**
     * @param array<int, CommentListItemDTO> $items
     */
    public function __construct(
        public array $items,
        public CommentPaginationDTO $pagination,
    ) {
    }
}
