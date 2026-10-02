<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces;

use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ExternalArticle;

/**
 * Chooses the hashtags an Imported Article gets. Implementations pick from a vocabulary the
 * platform controls rather than inventing tags, so imports do not fragment the tag space.
 */
interface ArticleTaggerInterface
{
    public const MAX_TAGS = 3;

    /**
     * @return list<string> at most MAX_TAGS hashtag names, possibly none
     */
    public function tagsFor(ContentSourceDTO $source, ExternalArticle $article): array;
}
