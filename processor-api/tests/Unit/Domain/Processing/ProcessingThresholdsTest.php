<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Processing;

use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\ProcessingThresholds;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProcessingThresholdsTest extends TestCase
{
    private const NOW = '2026-10-27 12:00:00';

    /**
     * @return array<string, array{ProcessingStatus, int|null, int|null, bool}> status, updated and created seconds ago, stale
     */
    public static function rows(): array
    {
        $threshold = ProcessingThresholds::STALE_AFTER_SECONDS;

        return [
            'completed long ago is never stale' => [ProcessingStatus::COMPLETED, 9999, 9999, false],
            'failed long ago is never stale' => [ProcessingStatus::FAILED, 9999, 9999, false],
            'superseded long ago is never stale' => [ProcessingStatus::SUPERSEDED, 9999, 9999, false],
            'processing, recent progress' => [ProcessingStatus::PROCESSING, 10, 9999, false],
            'processing, one second inside the threshold' => [ProcessingStatus::PROCESSING, $threshold - 1, 9999, false],
            'processing, exactly at the threshold' => [ProcessingStatus::PROCESSING, $threshold, 9999, true],
            'pending, past the threshold' => [ProcessingStatus::PENDING, $threshold + 60, 9999, true],
            'no update, created recently' => [ProcessingStatus::PENDING, null, 10, false],
            'no update, created past the threshold' => [ProcessingStatus::PENDING, null, $threshold + 1, true],
            'no timestamps at all' => [ProcessingStatus::PROCESSING, null, null, true],
        ];
    }

    #[DataProvider('rows')]
    public function test_the_stale_rule(ProcessingStatus $status, ?int $updatedSecondsAgo, ?int $createdSecondsAgo, bool $stale): void
    {
        $now = new DateTimeImmutable(self::NOW);

        self::assertSame($stale, ProcessingThresholds::isStale(
            $status,
            $updatedSecondsAgo === null ? null : $now->modify("-{$updatedSecondsAgo} seconds"),
            $createdSecondsAgo === null ? null : $now->modify("-{$createdSecondsAgo} seconds"),
            $now,
        ));
    }

    public function test_the_cutoff_is_the_threshold_before_now(): void
    {
        self::assertSame(
            '2026-10-27 11:54:30',
            ProcessingThresholds::staleBefore(new DateTimeImmutable(self::NOW))->format('Y-m-d H:i:s'),
        );
    }
}
