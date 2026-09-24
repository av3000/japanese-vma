<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Stats\Interfaces\Readers;

use App\Domain\JapaneseMaterial\Stats\DTOs\CorpusStatsDTO;

/**
 * Read port for the corpus totals. The bound implementation may serve a cached value,
 * so callers must not rely on it reflecting writes made in the same request.
 */
interface CorpusStatsReaderInterface
{
    public function read(): CorpusStatsDTO;
}
