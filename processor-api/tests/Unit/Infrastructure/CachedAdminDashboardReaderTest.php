<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Domain\AdminDashboard\DTOs\AttentionCountsDTO;
use App\Infrastructure\Persistence\Readers\CachedAdminDashboardReader;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * The cache decorator swallows cache errors only, and never runs the live read twice.
 */
final class CachedAdminDashboardReaderTest extends TestCase
{
    private const KEY = CachedAdminDashboardReader::KEY_PREFIX.'attention-counts';

    public function test_a_cache_hit_does_not_touch_the_live_reader(): void
    {
        $inner = $this->createMock(AdminDashboardReaderInterface::class);
        $inner->expects(self::never())->method('attentionCounts');
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->with(self::KEY)->willReturn($this->counts()->toArray());

        $counts = (new CachedAdminDashboardReader($inner, $cache))->attentionCounts();

        self::assertSame(3, $counts->failedJobs);
    }

    public function test_a_failing_cache_read_falls_back_to_one_live_read(): void
    {
        Log::spy();
        $inner = $this->innerReturningCounts();
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->willThrowException(new RuntimeException('redis down'));
        $cache->expects(self::never())->method('put');

        $counts = (new CachedAdminDashboardReader($inner, $cache))->attentionCounts();

        self::assertSame(3, $counts->failedJobs);
        Log::shouldHaveReceived('warning')->withArgs(static fn (string $message): bool => str_contains($message, 'cache unavailable'))->once();
    }

    public function test_a_failing_cache_write_still_returns_the_one_live_read(): void
    {
        Log::spy();
        $inner = $this->innerReturningCounts();
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->willReturn(null);
        $cache->method('put')->willThrowException(new RuntimeException('redis read-only'));

        $counts = (new CachedAdminDashboardReader($inner, $cache))->attentionCounts();

        self::assertSame(3, $counts->failedJobs);
        Log::shouldHaveReceived('warning')->withArgs(static fn (string $message): bool => str_contains($message, 'cache write failed'))->once();
    }

    public function test_a_failing_live_read_propagates_once_and_is_not_blamed_on_the_cache(): void
    {
        Log::spy();
        $inner = $this->createMock(AdminDashboardReaderInterface::class);
        $inner->expects(self::once())->method('attentionCounts')->willThrowException(new RuntimeException('query failed'));
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->willReturn(null);
        $cache->expects(self::never())->method('put');

        try {
            (new CachedAdminDashboardReader($inner, $cache))->attentionCounts();
            self::fail('The live read error should reach the caller.');
        } catch (RuntimeException $exception) {
            self::assertSame('query failed', $exception->getMessage());
        }

        Log::shouldNotHaveReceived('warning');
    }

    private function innerReturningCounts(): AdminDashboardReaderInterface
    {
        $inner = $this->createMock(AdminDashboardReaderInterface::class);
        $inner->expects(self::once())->method('attentionCounts')->willReturn($this->counts());

        return $inner;
    }

    private function counts(): AttentionCountsDTO
    {
        return new AttentionCountsDTO(articlesAwaitingReview: 1, processingFailedLastDay: 0, processingStuck: 2, failedJobs: 3);
    }
}
