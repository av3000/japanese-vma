<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

use App\Domain\Catalogues\Models\Catalogue;

readonly class CatalogueDetailDTO
{
    public function __construct(
        public Catalogue $catalogue,
        public array $items,
        public int $itemsCount
    ) {}
}
