<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

readonly class CatalogueListResultDTO
{
    /**
     * @param array<int, CatalogueListItemDTO> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {
    }
}
