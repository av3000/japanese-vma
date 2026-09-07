<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

/**
 * Page metadata expressed without a Laravel paginator, so application and domain
 * results never hand a framework object to a caller.
 */
final readonly class ArticlePaginationDTO
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total,
        public int $lastPage,
        public bool $hasMore,
    ) {
    }

    /**
     * @return array{page: int, per_page: int, total: int, last_page: int, has_more: bool}
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage,
            'has_more' => $this->hasMore,
        ];
    }
}
