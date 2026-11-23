<?php

declare(strict_types=1);

namespace App\Domain\Processing;

use App\Domain\Processing\Enums\ProcessingStatus;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * When a non-terminal processing row counts as stale ("stuck"). The rule lives here once, as a
 * PHP predicate; ProcessingState::scopeStale() is its SQL twin, and a test keeps the two in step.
 * The requeue guard, the processing monitor, the dashboard and the sweeper all use one or the
 * other, so they agree on what "stuck" means.
 *
 * Last progress is `updated_at`, falling back to `created_at`. A non-terminal row is stale when
 * its last progress is at or before the cutoff, or when it has neither timestamp, because nothing
 * shows it is alive. Otherwise it is running: for non-terminal rows the two are exact complements.
 */
final class ProcessingThresholds
{
    /**
     * Job timeout (120 s) + queue retry_after (180 s) + margin. A row that has not been touched
     * for that long has no attempt left that could still update it.
     */
    public const STALE_AFTER_SECONDS = 330;

    /**
     * Rows whose last progress is at or before this moment are stale.
     */
    public static function staleBefore(DateTimeInterface $now): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($now)->sub(new DateInterval('PT'.self::STALE_AFTER_SECONDS.'S'));
    }

    public static function isStale(
        ProcessingStatus $status,
        ?DateTimeInterface $updatedAt,
        ?DateTimeInterface $createdAt,
        DateTimeInterface $now,
    ): bool {
        return self::isStaleBefore($status, $updatedAt, $createdAt, self::staleBefore($now));
    }

    /**
     * The same rule against an explicit cutoff, for the sweeper's `--older-than` option.
     */
    public static function isStaleBefore(
        ProcessingStatus $status,
        ?DateTimeInterface $updatedAt,
        ?DateTimeInterface $createdAt,
        DateTimeInterface $cutoff,
    ): bool {
        if ($status->isTerminal()) {
            return false;
        }

        $lastProgress = $updatedAt ?? $createdAt;

        return $lastProgress === null || $lastProgress->getTimestamp() <= $cutoff->getTimestamp();
    }
}
