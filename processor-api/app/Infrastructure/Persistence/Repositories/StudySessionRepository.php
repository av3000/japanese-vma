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
use Illuminate\Database\UniqueConstraintViolationException;

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
        // The unique (session, item, attempt_no) constraint is the duplicate check; a
        // pre-read would be a second round trip per answer for the same answer.
        try {
            StudyAttempt::create([
                'session_id' => $sessionId,
                'item_id' => $attempt->itemId,
                'entity_type_uuid' => $itemType->value,
                'attempt_no' => $attempt->attemptNo,
                'given_answer' => $attempt->givenAnswer,
                'expected_answers' => $attempt->expectedAnswers,
                'is_correct' => $attempt->isCorrect,
                'response_ms' => $attempt->responseMs,
                'answered_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // A retried request: the row is already there, and the first verdict stands.
            return false;
        }

        return true;
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
