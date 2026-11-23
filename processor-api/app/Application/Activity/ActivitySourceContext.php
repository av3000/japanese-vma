<?php

declare(strict_types=1);

namespace App\Application\Activity;

use App\Domain\Activity\Enums\ActivitySource;

/**
 * Which surface the current request or job came through, set by whoever owns that surface instead
 * of guessed from auth guards: the `activity.source` middleware for the API, PanelActor for the
 * admin panel, and the container for console runs and queued jobs. Bound as a scoped instance, so
 * every request and every queued job starts again from its default.
 */
final class ActivitySourceContext
{
    public function __construct(private ?ActivitySource $source = null)
    {
    }

    public function set(ActivitySource $source): void
    {
        $this->source = $source;
    }

    public function current(): ?ActivitySource
    {
        return $this->source;
    }
}
