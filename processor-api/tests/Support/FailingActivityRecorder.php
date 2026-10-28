<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Domain\Activity\DTOs\ActivityEntry;
use RuntimeException;

/**
 * An activity log that cannot be written. Bound in place of the real recorder to prove a staff
 * change and its activity row commit together: when the row fails, the change rolls back.
 */
final class FailingActivityRecorder implements ActivityRecorderInterface
{
    public function record(ActivityEntry $entry): void
    {
        throw new RuntimeException('Simulated activity log failure');
    }
}
