<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\DTOs;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;

final readonly class KanjiListResultDTO
{
    /**
     * @param array<int, Kanji> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {
    }
}
