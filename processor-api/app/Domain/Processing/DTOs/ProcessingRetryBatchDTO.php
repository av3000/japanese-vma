<?php

declare(strict_types=1);

namespace App\Domain\Processing\DTOs;

/**
 * Outcome of retrying a selection of processing runs.
 */
final readonly class ProcessingRetryBatchDTO
{
    /**
     * @param list<string> $failures "label: reason" per run that could not be queued
     * @param list<string> $skipped "label: reason" per run left alone on purpose (still running,
     *                              or a type that cannot be retried)
     */
    public function __construct(
        public int $queued,
        public array $failures,
        public array $skipped,
    ) {
    }
}
