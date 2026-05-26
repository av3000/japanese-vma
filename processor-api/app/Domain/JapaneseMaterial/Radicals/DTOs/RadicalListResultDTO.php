<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Radicals\DTOs;

use App\Domain\JapaneseMaterial\Radicals\Models\Radical;

final readonly class RadicalListResultDTO
{
    /**
     * @param array<int, Radical> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {}
}
