<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport\Tagging;

use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ExternalArticle;

/**
 * Tags nothing. Bound until a source has a vocabulary mapping.
 */
class NullArticleTagger implements ArticleTaggerInterface
{
    public function tagsFor(ContentSourceDTO $source, ExternalArticle $article): array
    {
        return [];
    }
}
