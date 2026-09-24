<?php

namespace Tests\Feature\JapaneseMaterial\Stats;

use App\Application\JapaneseMaterial\Stats\Interfaces\Caches\CorpusStatsCacheInterface;
use App\Support\JapaneseDataImport\JapaneseDataImporter;
use RuntimeException;
use Tests\TestCase;

class ImportJapaneseDataClearsCorpusStatsTest extends TestCase
{
    private RecordingCorpusStatsCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new RecordingCorpusStatsCache();
        $this->app->instance(CorpusStatsCacheInterface::class, $this->cache);
    }

    public function test_successful_import_forgets_the_corpus_stats(): void
    {
        $this->fakeImporterReturning([
            'skipped' => false,
            'message' => 'done',
            'datasets' => ['japanese_kanji_bank_long' => 2],
        ]);

        $this->artisan('app:import-japanese-data')->assertSuccessful();

        $this->assertSame(1, $this->cache->forgets);
    }

    public function test_skipped_import_keeps_the_corpus_stats(): void
    {
        $this->fakeImporterReturning([
            'skipped' => true,
            'message' => 'Already imported.',
            'datasets' => [],
        ]);

        $this->artisan('app:import-japanese-data')->assertSuccessful();

        $this->assertSame(0, $this->cache->forgets);
    }

    public function test_failed_import_keeps_the_corpus_stats(): void
    {
        $importer = $this->createMock(JapaneseDataImporter::class);
        $importer->method('import')->willThrowException(new RuntimeException('boom'));
        $this->app->instance(JapaneseDataImporter::class, $importer);

        $this->artisan('app:import-japanese-data')->assertFailed();

        $this->assertSame(0, $this->cache->forgets);
    }

    /**
     * @param array{skipped: bool, message: string, datasets: array<string, int>} $result
     */
    private function fakeImporterReturning(array $result): void
    {
        $importer = $this->createMock(JapaneseDataImporter::class);
        $importer->method('import')->willReturn($result);
        $this->app->instance(JapaneseDataImporter::class, $importer);
    }
}

final class RecordingCorpusStatsCache implements CorpusStatsCacheInterface
{
    public int $forgets = 0;

    public function forget(): void
    {
        $this->forgets++;
    }
}
