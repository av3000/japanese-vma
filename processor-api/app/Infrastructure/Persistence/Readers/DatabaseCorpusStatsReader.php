<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\JapaneseMaterial\Stats\Interfaces\Readers\CorpusStatsReaderInterface;
use App\Domain\JapaneseMaterial\Stats\DTOs\CorpusStatsDTO;
use App\Infrastructure\Persistence\Models\Kanji;
use App\Infrastructure\Persistence\Models\Radical;
use App\Infrastructure\Persistence\Models\Sentence;
use App\Infrastructure\Persistence\Models\Word;

/**
 * Exact totals. On PostgreSQL each count is a full scan (the word bank alone is ~185k rows),
 * which is why this reader is bound behind CachedCorpusStatsReader rather than used directly.
 */
final readonly class DatabaseCorpusStatsReader implements CorpusStatsReaderInterface
{
    public function read(): CorpusStatsDTO
    {
        return new CorpusStatsDTO(
            radicals: Radical::query()->count(),
            kanjis: Kanji::query()->count(),
            words: Word::query()->count(),
            sentences: Sentence::query()->count(),
        );
    }
}
