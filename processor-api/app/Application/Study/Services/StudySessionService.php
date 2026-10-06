<?php

declare(strict_types=1);

namespace App\Application\Study\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Study\Interfaces\Repositories\StudySessionRepositoryInterface;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\StudyAttemptDTO;
use App\Domain\Study\DTOs\StudySessionCreateDTO;
use App\Domain\Study\Errors\StudyErrors;
use App\Domain\Study\Models\StudySession;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use App\Shared\Results\Result;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

final class StudySessionService implements StudySessionServiceInterface
{
    public function __construct(
        private readonly CatalogueServiceInterface $catalogueService,
        private readonly StudySessionRepositoryInterface $sessions,
    ) {
    }

    public function createSession(StudySessionCreateDTO $dto, AuthenticatedUser $user): Result
    {
        $catalogueResult = $this->catalogueService->getViewableCatalogue($dto->catalogueUuid, $user);

        if ($catalogueResult->isFailure()) {
            return $catalogueResult;
        }

        /** @var Catalogue $catalogue */
        $catalogue = $catalogueResult->getData();
        $type = $catalogue->getType();

        if (! FlashcardQuestion::supportsType($type)) {
            return Result::failure(StudyErrors::catalogueTypeNotSupported($dto->catalogueUuid->value(), $type->label()));
        }

        if (! $dto->question->isValidFor($type)) {
            return Result::failure(StudyErrors::invalidFieldCombination(
                $dto->catalogueUuid->value(),
                $dto->question->prompt->value,
                $dto->question->answer->value,
                $dto->question->mode->value,
            ));
        }

        try {
            $session = $this->sessions->create(new StudySession(
                id: null,
                uuid: EntityId::generate(),
                userId: $user->id,
                catalogueId: $catalogue->getIdValue(),
                catalogueUuid: $catalogue->getUid(),
                catalogueType: $type,
                prompt: $dto->question->prompt,
                answer: $dto->question->answer,
                mode: $dto->question->mode,
                script: $dto->question->script,
                cardCount: $dto->cardCount,
                correctCount: null,
                startedAt: new DateTimeImmutable,
                completedAt: null,
            ));

            return Result::success($session);
        } catch (\Throwable $e) {
            Log::error('Study session creation failed', [
                'user_id' => $user->id->value(),
                'catalogue_uuid' => $dto->catalogueUuid->value(),
                'error' => $e->getMessage(),
            ]);

            return Result::failure(StudyErrors::sessionCreationFailed());
        }
    }

    public function recordAttempt(EntityId $sessionUuid, StudyAttemptDTO $attempt, AuthenticatedUser $user): Result
    {
        $sessionResult = $this->ownSession($sessionUuid, $user);

        if ($sessionResult->isFailure()) {
            return $sessionResult;
        }

        /** @var StudySession $session */
        $session = $sessionResult->getData();

        if (! $session->acceptsAttempt($attempt->attemptNo)) {
            return Result::failure(StudyErrors::sessionCompleted($sessionUuid->value()));
        }

        $itemType = FlashcardQuestion::itemTypeFor($session->getCatalogueType());

        if ($itemType === null) {
            return Result::failure(StudyErrors::catalogueTypeNotSupported($sessionUuid->value(), $session->getCatalogueType()->label()));
        }

        // Phase 2 reads per-item statistics by (type, item); keep junk ids out while the
        // catalogue still exists to check against. A deleted catalogue leaves no reference.
        $catalogueId = $session->getCatalogueId();

        if ($catalogueId !== null) {
            $containsResult = $this->catalogueService->catalogueContainsItem($catalogueId, $attempt->itemId);

            if ($containsResult->isFailure()) {
                return $containsResult;
            }

            if ($containsResult->getData() !== true) {
                return Result::failure(StudyErrors::itemNotInCatalogue($sessionUuid->value(), $attempt->itemId));
            }
        }

        return Result::success($this->sessions->recordAttempt($session->getIdValue(), $itemType, $attempt));
    }

    public function completeSession(EntityId $sessionUuid, int $correctCount, AuthenticatedUser $user): Result
    {
        $sessionResult = $this->ownSession($sessionUuid, $user);

        if ($sessionResult->isFailure()) {
            return $sessionResult;
        }

        /** @var StudySession $session */
        $session = $sessionResult->getData();

        if ($session->isCompleted()) {
            return Result::success($session);
        }

        return Result::success($this->sessions->complete(
            $session->getIdValue(),
            min($correctCount, $session->getCardCount()),
        ));
    }

    /**
     * @return Result Success data: StudySession.
     */
    private function ownSession(EntityId $sessionUuid, AuthenticatedUser $user): Result
    {
        $session = $this->sessions->findByUuid($sessionUuid);

        // Both branches answer the same error on purpose, see the interface.
        if ($session === null || ! $session->isOwnedBy($user->id)) {
            return Result::failure(StudyErrors::sessionNotFound($sessionUuid->value()));
        }

        return Result::success($session);
    }
}
