<?php

declare(strict_types=1);

namespace App\Application\Study\Interfaces\Repositories;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\StudyAttemptDTO;
use App\Domain\Study\Models\StudySession;

interface StudySessionRepositoryInterface
{
    public function create(StudySession $session): StudySession;

    public function findByUuid(EntityId $uuid): ?StudySession;

    /**
     * Stores one answer. Returns false when the same `(session, item, attempt_no)` is
     * already recorded, so a retried request is a no-op rather than a second row.
     */
    public function recordAttempt(int $sessionId, ObjectTemplateType $itemType, StudyAttemptDTO $attempt): bool;

    public function complete(int $sessionId, int $correctCount): StudySession;
}
