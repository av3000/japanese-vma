<?php

declare(strict_types=1);

namespace App\Application\Activity;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Activity\DTOs\ActivityEntry;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySource;
use App\Domain\Activity\Enums\ActivitySubjectType;

/**
 * Builds activity entries from the application-level actor, so services do not repeat the
 * actor-to-row mapping. A null actor is a console or system run.
 */
final class ActivityEntries
{
    public const SYSTEM_ACTOR_NAME = 'system';

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, scalar|array<mixed>|null> $context
     */
    public static function by(
        ?AuthenticatedUser $actor,
        ActivityAction $action,
        ActivitySubjectType $subjectType,
        string $subjectId,
        ?string $subjectLabel = null,
        array $before = [],
        array $after = [],
        array $context = [],
        ?ActivitySource $source = null,
    ): ActivityEntry {
        return new ActivityEntry(
            actorId: $actor?->id->value(),
            actorName: $actor?->name->value() ?? self::SYSTEM_ACTOR_NAME,
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectLabel: $subjectLabel,
            before: $before,
            after: $after,
            context: $context,
            source: $source,
        );
    }
}
