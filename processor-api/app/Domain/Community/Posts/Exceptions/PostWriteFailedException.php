<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\Exceptions;

use App\Shared\Results\ResultError;
use RuntimeException;

/**
 * Carries a ResultError out of a transaction closure.
 *
 * A collaborator inside the transaction (hashtag sync) reports failure as a
 * Result rather than by throwing, but returning early from the closure would
 * commit the partial write. Throwing this rolls the transaction back and keeps
 * the original error, so the caller still answers 422 instead of a blanket 500.
 */
final class PostWriteFailedException extends RuntimeException
{
    public function __construct(public readonly ResultError $error)
    {
        parent::__construct($error->detail ?? $error->description);
    }
}
