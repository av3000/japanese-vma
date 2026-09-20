<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;

/**
 * One page of the kanji attached to an article (issue #268).
 *
 * The detail response used to carry every attached kanji; this is what replaced it, so a
 * reader pays for the rows it shows rather than for everything processing ever found.
 */
final readonly class ArticleKanjiListResultDTO
{
    /**
     * @param array<int, Kanji> $items
     */
    public function __construct(
        public array $items,
        public ArticlePaginationDTO $pagination,
    ) {
    }
}
