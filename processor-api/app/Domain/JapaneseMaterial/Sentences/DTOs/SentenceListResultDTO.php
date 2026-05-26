<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\DTOs;

use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;

final readonly class SentenceListResultDTO
{
    /**
     * @param array<int, Sentence> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {}
}
