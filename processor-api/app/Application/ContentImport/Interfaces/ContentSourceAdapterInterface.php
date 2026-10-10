<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces;

use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;

/**
 * Reads one Content Source. One class per source; adding a source means writing one of these
 * and seeding its `content_sources` row.
 */
interface ContentSourceAdapterInterface
{
    /**
     * The `content_sources.key` this adapter serves.
     */
    public function key(): string;

    /**
     * Newest first, lazily. The adapter skips every id `$isKnown` reports as imported before,
     * without fetching it, and keeps going: an article that failed on an earlier run is listed
     * again for as long as the source still has it. The caller stops consuming once it has
     * enough, so the source's own window and the run's ceilings bound the listing.
     *
     * @param callable(string): bool $isKnown
     *
     * @throws ContentSourceUnavailableException when the source as a whole cannot be read
     *
     * @return iterable<ExternalArticle>
     */
    public function listRecent(callable $isKnown): iterable;
}
