<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;

/**
 * Normalized user intent for an Article list request.
 *
 * This is the "AND user intent" half of the list invariant. It deliberately carries
 * no visibility information: eligibility is derived from the actor by
 * ArticleVisibilityPolicy, so no query parameter can widen access.
 *
 * It also carries no HTTP or Eloquent types. AFM-03 replaces `category`, and the
 * singular `kanjiId`/`wordId`, with the canonical plural filter contract.
 */
final readonly class ArticleListQuery
{
    public function __construct(
        public ArticleSortCriteria $sort,
        public Pagination $pagination,
        public ?SearchTerm $search = null,
        public ?int $categoryId = null,
        public ?string $authorUid = null,
        public ?int $kanjiId = null,
        public ?int $wordId = null,
    ) {
    }
}
