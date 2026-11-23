<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Stats;

use App\Application\JapaneseMaterial\Stats\Interfaces\Readers\CorpusStatsReaderInterface;
use App\Domain\JapaneseMaterial\Stats\DTOs\CorpusStatsDTO;
use App\Infrastructure\Persistence\Readers\CachedCorpusStatsReader;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

class CachedCorpusStatsReaderTest extends TestCase
{
    public function testReadsTheInnerReaderOnceWhileCached(): void
    {
        $inner = new CountingCorpusStatsReader(new CorpusStatsDTO(radicals: 1, kanjis: 2, words: 3, sentences: 4));
        $reader = new CachedCorpusStatsReader($inner, new Repository(new ArrayStore()));

        $first = $reader->read();
        $second = $reader->read();

        $this->assertSame(1, $inner->calls);
        $this->assertEquals($first, $second);
        $this->assertSame(4, $second->sentences);
    }

    public function testForgetMakesTheNextReadHitTheInnerReaderAgain(): void
    {
        $inner = new CountingCorpusStatsReader(new CorpusStatsDTO(radicals: 1, kanjis: 2, words: 3, sentences: 4));
        $reader = new CachedCorpusStatsReader($inner, new Repository(new ArrayStore()));

        $reader->read();
        $inner->next = new CorpusStatsDTO(radicals: 1, kanjis: 2, words: 3, sentences: 5);
        $reader->forget();

        $this->assertSame(5, $reader->read()->sentences);
        $this->assertSame(2, $inner->calls);
    }

    public function testCachesForOneHour(): void
    {
        $this->assertSame(3600, CachedCorpusStatsReader::TTL_SECONDS);
    }
}

final class CountingCorpusStatsReader implements CorpusStatsReaderInterface
{
    public int $calls = 0;

    public function __construct(public CorpusStatsDTO $next)
    {
    }

    public function read(): CorpusStatsDTO
    {
        $this->calls++;

        return $this->next;
    }
}
