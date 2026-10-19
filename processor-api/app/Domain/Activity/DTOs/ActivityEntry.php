<?php

declare(strict_types=1);

namespace App\Domain\Activity\DTOs;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;

/**
 * One privileged mutation: who did what to which entity, with before/after values.
 *
 * The actor is passed in explicitly rather than read from the session, because the panel signs in
 * on the `web` guard and the API on `api`; a null actor id means a console or system run.
 */
final readonly class ActivityEntry
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, scalar|array<mixed>|null> $context free-form extras such as a reason or moved ids
     */
    public function __construct(
        public ?int $actorId,
        public string $actorName,
        public ActivityAction $action,
        public ActivitySubjectType $subjectType,
        public string $subjectId,
        public ?string $subjectLabel = null,
        public array $before = [],
        public array $after = [],
        public array $context = [],
    ) {
    }

    /**
     * Keep only the attributes the subject type allows, and only those that actually changed
     * when both sides are present.
     *
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function loggableChanges(): array
    {
        $allowed = array_flip($this->subjectType->loggableAttributes());
        $before = array_intersect_key($this->before, $allowed);
        $after = array_intersect_key($this->after, $allowed);

        if ($before !== [] && $after !== []) {
            foreach (array_keys($after) as $key) {
                if (array_key_exists($key, $before) && $before[$key] === $after[$key]) {
                    unset($before[$key], $after[$key]);
                }
            }
        }

        return ['before' => $before, 'after' => $after];
    }
}
