<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\DTOs;

/**
 * A registered Content Source as the import sees it.
 */
final readonly class ContentSourceDTO
{
    public function __construct(
        public int $id,
        public string $key,
        public string $name,
        public string $homepageUrl,
        public bool $enabled,
    ) {
    }
}
