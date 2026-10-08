<?php

declare(strict_types=1);

namespace App\Application\Study\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\StudyAttemptDTO;
use App\Domain\Study\DTOs\StudySessionCreateDTO;
use App\Domain\Study\Models\StudySession;
use App\Shared\Results\Result;

interface StudySessionServiceInterface
{
    /**
     * Failures: Catalogues.NotFound, Catalogues.AccessDenied, Study.CatalogueTypeNotSupported,
     * Study.InvalidFieldCombination, Study.SessionCreationFailed.
     *
     * @return Result Success data: StudySession.
     */
    public function createSession(StudySessionCreateDTO $dto, AuthenticatedUser $user): Result;

    /**
     * A session that is not the caller's answers Study.SessionNotFound, never 403: the
     * endpoint must not confirm that someone else's session uuid exists. A first-pass
     * attempt (attempt_no 1) on a completed session is Study.SessionCompleted; retry rounds
     * (attempt_no 2 and up) are accepted after completion.
     *
     * @return Result Success data: bool, true when a row was written, false when the attempt already existed.
     */
    public function recordAttempt(EntityId $sessionUuid, StudyAttemptDTO $attempt, AuthenticatedUser $user): Result;

    /**
     * Idempotent: completing a completed session returns it unchanged.
     *
     * @return Result Success data: StudySession.
     */
    public function completeSession(EntityId $sessionUuid, int $correctCount, AuthenticatedUser $user): Result;
}
