<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Activity\ActivitySourceContext;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Domain\Activity\DTOs\ActivityEntry;
use App\Domain\Activity\Enums\ActivitySource;
use App\Infrastructure\Persistence\Models\AdminActivity;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;

/**
 * Writes one admin_activities row per entry. The source comes from the entry or from the
 * request's ActivitySourceContext, never from guessing at auth guards.
 *
 * Strict outside production: a missing source, a snapshot key the subject type does not allow, or
 * a secret-looking context key throws, so a renamed key or a forgotten source fails a test instead
 * of silently losing or leaking audit data. In production the same problems are logged and the
 * row is still written, because a staff action should not fail over its log entry's shape.
 */
final class DatabaseActivityRecorder implements ActivityRecorderInterface
{
    /** Context keys that would only ever hold a secret. Flags such as `password_changed` are fine. */
    private const SECRET_CONTEXT_KEYS = ['password', 'remember_token', 'token', 'secret'];

    public function __construct(
        private readonly ActivitySourceContext $sourceContext,
        private readonly bool $strict,
    ) {
    }

    public function record(ActivityEntry $entry): void
    {
        $this->guardSnapshotKeys($entry);
        $context = $this->safeContext($entry);

        $changes = $entry->loggableChanges();

        AdminActivity::query()->create([
            'actor_id' => $entry->actorId,
            'actor_name' => $entry->actorName,
            'action' => $entry->action,
            'subject_type' => $entry->subjectType,
            'subject_id' => $entry->subjectId,
            'subject_label' => $entry->subjectLabel === null ? null : mb_substr($entry->subjectLabel, 0, 255),
            'changes' => $changes['before'] === [] && $changes['after'] === [] ? null : $changes,
            'source' => $this->source($entry),
            'context' => $context === [] ? null : $context,
        ]);
    }

    private function source(ActivityEntry $entry): ActivitySource
    {
        $source = $entry->source ?? $this->sourceContext->current();

        if ($source !== null) {
            return $source;
        }

        if ($this->strict) {
            throw new LogicException("No activity source for {$entry->action->value}: set ActivitySourceContext or pass the source on the entry.");
        }

        Log::warning('Activity recorded without a source; stored as API', ['action' => $entry->action->value]);

        return ActivitySource::Api;
    }

    private function guardSnapshotKeys(ActivityEntry $entry): void
    {
        $unknown = $entry->unknownAttributes();

        if ($unknown === []) {
            return;
        }

        if ($this->strict) {
            throw new InvalidArgumentException(sprintf(
                'Snapshot keys not allowed for %s: %s. Add them to ActivitySubjectType::loggableAttributes() or stop sending them.',
                $entry->subjectType->value,
                implode(', ', $unknown),
            ));
        }

        Log::warning('Activity snapshot keys dropped', ['subject_type' => $entry->subjectType->value, 'keys' => $unknown]);
    }

    /**
     * @return array<string, mixed>
     */
    private function safeContext(ActivityEntry $entry): array
    {
        $secrets = array_values(array_intersect(array_keys($entry->context), self::SECRET_CONTEXT_KEYS));

        if ($secrets === []) {
            return $entry->context;
        }

        if ($this->strict) {
            throw new InvalidArgumentException('Activity context must not hold secrets: '.implode(', ', $secrets));
        }

        Log::warning('Secret-looking activity context keys dropped', ['action' => $entry->action->value, 'keys' => $secrets]);

        return array_diff_key($entry->context, array_flip($secrets));
    }
}
