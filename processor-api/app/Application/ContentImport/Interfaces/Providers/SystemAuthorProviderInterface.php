<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces\Providers;

use App\Domain\Articles\ValueObjects\ArticleAuthor;

interface SystemAuthorProviderInterface
{
    /**
     * The seeded system user that authors every Imported Article, or null when it is missing.
     */
    public function author(): ?ArticleAuthor;
}
