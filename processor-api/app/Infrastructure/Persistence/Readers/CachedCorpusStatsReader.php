<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\JapaneseMaterial\Stats\Interfaces\Caches\CorpusStatsCacheInterface;
use App\Application\JapaneseMaterial\Stats\Interfaces\Readers\CorpusStatsReaderInterface;
use App\Domain\JapaneseMaterial\Stats\DTOs\CorpusStatsDTO;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Serves the corpus totals from the shared cache for an hour. Radicals, kanji and words only
 * change through the dictionary import, which calls forget(); sentences can also be written by
 * users, so the sentence total may lag by up to the TTL. That is accepted for a display figure.
 *
 * The cached value is a plain array rather than the DTO so a change to the DTO class between
 * deploys cannot leave an unserialisable entry behind.
 */
final readonly class CachedCorpusStatsReader implements CorpusStatsCacheInterface, CorpusStatsReaderInterface
{
    public const CACHE_KEY = 'japanese-material:corpus-stats:v1';

    public const TTL_SECONDS = 3600;

    public function __construct(
        private CorpusStatsReaderInterface $inner,
        private CacheRepository $cache,
    ) {
    }

    public function read(): CorpusStatsDTO
    {
        /** @var array{radicals: int, kanjis: int, words: int, sentences: int} $values */
        $values = $this->cache->remember(
            self::CACHE_KEY,
            self::TTL_SECONDS,
            fn (): array => $this->inner->read()->toArray(),
        );

        return CorpusStatsDTO::fromArray($values);
    }

    public function forget(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
