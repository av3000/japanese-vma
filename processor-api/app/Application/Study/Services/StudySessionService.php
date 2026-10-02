<?php

declare(strict_types=1);

namespace App\Application\Study\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Study\Interfaces\Repositories\StudySessionRepositoryInterface;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\StudyAttemptDTO;
use App\Domain\Study\DTOs\StudySessionCreateDTO;
use App\Domain\Study\Errors\StudyErrors;
use App\Domain\Study\Models\StudySession;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Shared\Results\Result;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

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

        if (! FlashcardConfig::supportsType($type)) {
            return Result::failure(StudyErrors::catalogueTypeNotSupported($dto->catalogueUuid->value(), $type->label()));
        }

        try {
            // The seed is irrelevant to a session; the value object only needs it to be in range.
            $config = new FlashcardConfig($dto->prompt, $dto->answer, $dto->mode, $dto->script, $dto->cardCount, 0);
        } catch (InvalidArgumentException) {
            $config = null;
        }

        if ($config === null || ! $config->isValidFor($type)) {
            return Result::failure(StudyErrors::invalidFieldCombination(
                $dto->catalogueUuid->value(),
                $dto->prompt->value,
                $dto->answer->value,
                $dto->mode->value,
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
                prompt: $dto->prompt,
                answer: $dto->answer,
                mode: $dto->mode,
                script: $dto->script,
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

        // The score is fixed once the first pass is complete, so no more first-pass answers.
        // "Retry missed" rounds (attempt_no 2 and up) happen after that and are still history.
        if ($session->isCompleted() && $attempt->attemptNo === 1) {
            return Result::failure(StudyErrors::sessionCompleted($sessionUuid->value()));
        }

        $itemType = self::itemTypeFor($session->getCatalogueType());

        if ($itemType === null) {
            return Result::failure(StudyErrors::catalogueTypeNotSupported($sessionUuid->value(), $session->getCatalogueType()->label()));
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

    /** The engagement vocabulary's id for the items a session's cards are built from. */
    public static function itemTypeFor(SavedListType $catalogueType): ?ObjectTemplateType
    {
        return match (FlashcardConfig::baseType($catalogueType)) {
            SavedListType::KANJIS => ObjectTemplateType::KANJI,
            SavedListType::WORDS => ObjectTemplateType::WORD,
            SavedListType::RADICALS => ObjectTemplateType::RADICAL,
            default => null,
        };
    }
}
