<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

readonly class CataloguePickerResultDTO
{
    /**
     * @param array<int, CataloguePickerItemDTO> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
