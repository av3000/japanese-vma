<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\DTOs;

use App\Domain\JapaneseMaterial\Words\Models\Word;

final readonly class WordListResultDTO
{
    /**
     * @param array<int, Word> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {
    }
}
