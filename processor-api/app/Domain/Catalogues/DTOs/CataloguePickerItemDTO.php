<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

use App\Domain\Catalogues\Models\Catalogue;

readonly class CataloguePickerItemDTO
{
    public function __construct(
        public Catalogue $catalogue,
        public bool $containsItem,
    ) {
    }
}
