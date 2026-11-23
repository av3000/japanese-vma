<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Study\Interfaces\Repositories\StudySessionRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\StudyAttemptDTO;
use App\Domain\Study\Models\StudySession as DomainStudySession;
use App\Infrastructure\Persistence\Models\StudyAttempt;
use App\Infrastructure\Persistence\Models\StudySession as PersistenceStudySession;

final class StudySessionRepository implements StudySessionRepositoryInterface
{
    public function __construct(
        private readonly StudySessionMapper $mapper,
    ) {
    }

    public function create(DomainStudySession $session): DomainStudySession
    {
        $entity = PersistenceStudySession::create($this->mapper->mapToEntity($session));
        $entity->load('catalogue');

        return $this->mapper->mapToDomain($entity);
    }

    public function findByUuid(EntityId $uuid): ?DomainStudySession
    {
        $entity = PersistenceStudySession::query()
            ->with('catalogue')
            ->where('uuid', $uuid->value())
            ->first();

        return $entity ? $this->mapper->mapToDomain($entity) : null;
    }

    public function recordAttempt(int $sessionId, ObjectTemplateType $itemType, StudyAttemptDTO $attempt): bool
    {
        // One statement: the unique (session, item, attempt_no) constraint is the duplicate
        // check and `ON CONFLICT DO NOTHING` reports a retried request as zero rows. Catching
        // a unique-violation exception is not an option here: PostgreSQL aborts the whole
        // transaction on any failed statement, so a caller's (or the test suite's) transaction
        // would be left unusable. `insertOrIgnore` bypasses the model casts, hence the JSON.
        $inserted = StudyAttempt::query()->insertOrIgnore([
            'session_id' => $sessionId,
            'item_id' => $attempt->itemId,
            'entity_type_uuid' => $itemType->value,
            'attempt_no' => $attempt->attemptNo,
            'given_answer' => $attempt->givenAnswer,
            'expected_answers' => json_encode($attempt->expectedAnswers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'is_correct' => $attempt->isCorrect,
            'response_ms' => $attempt->responseMs,
            'answered_at' => now(),
        ]);

        return $inserted === 1;
    }

    public function complete(int $sessionId, int $correctCount): DomainStudySession
    {
        $entity = PersistenceStudySession::query()->with('catalogue')->findOrFail($sessionId);
        $entity->correct_count = $correctCount;
        $entity->completed_at = now()->toImmutable();
        $entity->save();

        return $this->mapper->mapToDomain($entity);
    }
}
