<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces\Readers;

interface ImportedArticleReaderInterface
{
    public function exists(int $contentSourceId, string $externalId): bool;
}
