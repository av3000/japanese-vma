<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

/**
 * One choice inside a facet dimension.
 *
 * `selected` is echoed back rather than recomputed on the client, so the UI never
 * has to reimplement the server's notion of what is active.
 */
final readonly class ArticleFacetValueDTO
{
    public function __construct(
        public string $key,
        public string $label,
        public int $count,
        public bool $selected,
    ) {
    }
}
