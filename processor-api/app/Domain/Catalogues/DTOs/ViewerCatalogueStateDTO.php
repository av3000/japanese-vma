<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

final readonly class ViewerCatalogueStateDTO
{
    public function __construct(
        public bool $isSaved,
        public ?bool $isKnown = null,
    ) {
    }
}
