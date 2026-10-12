<?php

declare(strict_types=1);

namespace App\Domain\Processing;

/**
 * When a non-terminal processing row counts as stuck. The sweeper, the admin dashboard and the
 * processing monitor all read this one value, so they agree on what "stuck" means.
 */
final class ProcessingThresholds
{
    /**
     * Job timeout (120 s) + queue retry_after (180 s) + margin. A row that has not been touched
     * for that long has no attempt left that could still update it.
     */
    public const STALE_AFTER_SECONDS = 330;
}
