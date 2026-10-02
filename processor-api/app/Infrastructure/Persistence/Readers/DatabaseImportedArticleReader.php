<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\ContentImport\Interfaces\Readers\ImportedArticleReaderInterface;
use App\Infrastructure\Persistence\Models\Article;

/**
 * Served by the (content_source_id, external_id) unique index on `articles`.
 */
class DatabaseImportedArticleReader implements ImportedArticleReaderInterface
{
    public function exists(int $contentSourceId, string $externalId): bool
    {
        return Article::query()
            ->where('content_source_id', $contentSourceId)
            ->where('external_id', $externalId)
            ->exists();
    }
}
