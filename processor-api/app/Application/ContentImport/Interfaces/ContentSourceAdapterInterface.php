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
     * Newest first, lazily. The adapter stops as soon as `$isKnown` reports an id that was
     * imported before, so a daily run only fetches what is new since the last one. The caller
     * may also stop consuming early once it has enough.
     *
     * @param callable(string): bool $isKnown
     *
     * @throws ContentSourceUnavailableException when the source as a whole cannot be read
     *
     * @return iterable<ExternalArticle>
     */
    public function listRecent(callable $isKnown): iterable;
}
