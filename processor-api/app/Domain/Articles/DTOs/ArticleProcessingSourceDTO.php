<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

/**
 * Exactly what the content-processing job needs to read from an article, and nothing else:
 * the version it is about to process and the two Japanese fields extraction runs over.
 */
final readonly class ArticleProcessingSourceDTO
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $contentVersion,
        public string $titleJp,
        public string $contentJp,
    ) {
    }

    public function wordExtractionText(): string
    {
        return $this->titleJp.$this->contentJp;
    }
}
