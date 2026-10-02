<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\DTOs;

use DateTimeImmutable;

/**
 * One article as a Content Source lists it, already normalised by the source's adapter. Only
 * the excerpt is carried: the title, the lead paragraph and the canonical link.
 */
final readonly class ExternalArticle
{
    /**
     * @param list<string> $genres the source's broad categories, in source order
     * @param array<string, string> $topics the source's own topic tags, id => display name
     */
    public function __construct(
        public string $externalId,
        public string $title,
        public string $lead,
        public string $canonicalUrl,
        public ?DateTimeImmutable $publishedAt,
        public array $genres = [],
        public array $topics = [],
    ) {
    }
}
