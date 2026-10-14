<?php

declare(strict_types=1);

namespace App\Domain\Articles\ValueObjects;

/**
 * The Content Source an Imported Article came from, as readers need it for attribution.
 */
final readonly class ArticleSource
{
    public function __construct(
        public string $key,
        public string $name,
        public string $homepageUrl,
    ) {
    }
}
