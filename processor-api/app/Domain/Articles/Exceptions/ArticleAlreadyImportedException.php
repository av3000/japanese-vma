<?php

declare(strict_types=1);

namespace App\Domain\Articles\Exceptions;

/**
 * An article with the same Content Source and external id already exists: another import run
 * created it first.
 */
class ArticleAlreadyImportedException extends \RuntimeException
{
    public function __construct(public readonly string $externalId, ?\Throwable $previous = null)
    {
        parent::__construct("Article already imported: {$externalId}", 0, $previous);
    }
}
