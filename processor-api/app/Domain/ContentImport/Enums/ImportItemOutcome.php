<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\Enums;

/**
 * What an Import Run did with one listed external article.
 */
enum ImportItemOutcome: string
{
    case Created = 'created';
    case WouldCreate = 'would_create';
    case AlreadyImported = 'already_imported';
    case FilteredGenre = 'filtered_genre';
    case FilteredTooShort = 'filtered_too_short';
    case Failed = 'failed';

    public function isSkip(): bool
    {
        return in_array($this, [self::AlreadyImported, self::FilteredGenre, self::FilteredTooShort], true);
    }
}
