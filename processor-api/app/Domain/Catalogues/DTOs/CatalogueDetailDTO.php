<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\Models\CatalogueStats;

readonly class CatalogueDetailDTO
{
    public function __construct(
        public Catalogue $catalogue,
        public array $items,
        public int $itemsCount,
        public CatalogueStats $stats,
        public array $hashtags,
        public bool $isLikedByViewer,
    ) {
    }
}
