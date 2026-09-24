<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Stats\Interfaces\Caches;

/**
 * Invalidation port for the cached corpus totals, used after a dictionary import.
 *
 * User-authored sentences deliberately do not call this: the landing page accepts up to
 * the cache TTL of lag rather than coupling every sentence write to a homepage widget.
 */
interface CorpusStatsCacheInterface
{
    public function forget(): void;
}
