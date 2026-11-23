<?php

declare(strict_types=1);

namespace App\Application\Activity\Interfaces;

use App\Domain\Activity\DTOs\ActivityEntry;

/**
 * Append-only log of privileged mutations, written by application services with explicit intent
 * (never by model observers). Call it inside the write's transaction where one exists, so a
 * rolled-back action leaves no row.
 */
interface ActivityRecorderInterface
{
    public function record(ActivityEntry $entry): void;
}
