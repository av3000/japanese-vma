<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\JapaneseMaterial\Words\Models\Word;

/**
 * One page of the words attached to an article (issue #268). Sibling of
 * ArticleKanjiListResultDTO; the two stay separate because their items are different
 * aggregates and a shared generic would type neither.
 */
final readonly class ArticleWordListResultDTO
{
    /**
     * @param array<int, Word> $items
     */
    public function __construct(
        public array $items,
        public ArticlePaginationDTO $pagination,
    ) {
    }
}
