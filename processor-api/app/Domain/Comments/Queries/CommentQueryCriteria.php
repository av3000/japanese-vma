<?php

declare(strict_types=1);

namespace App\Domain\Comments\Queries;

use App\Domain\Comments\ValueObjects\CommentSortCriteria;
use App\Domain\Shared\ValueObjects\Pagination;

/**
 * What the client controls about a thread read: ordering and which page.
 *
 * The entity being commented on is deliberately absent. It is the subject of the
 * read, resolved server-side from the route, and no client input may redirect it
 * - so it travels as its own argument rather than as a field a caller could set.
 */
final readonly class CommentQueryCriteria
{
    public function __construct(
        public CommentSortCriteria $sort,
        public Pagination $pagination,
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            sort: CommentSortCriteria::fromValidated($validated),
            pagination: Pagination::fromInputOrDefault(
                isset($validated['page']) ? (int) $validated['page'] : null,
                isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            ),
        );
    }

    public static function default(): self
    {
        return new self(
            sort: CommentSortCriteria::default(),
            pagination: Pagination::default(),
        );
    }
}
