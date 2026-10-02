<?php

declare(strict_types=1);

namespace App\Domain\Study\Models;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use DateTimeImmutable;

/**
 * One learner's run through one deck. The catalogue reference is nullable because a
 * deleted catalogue must not take the learner's history with it; `catalogueType` is a
 * snapshot for the same reason.
 */
final readonly class StudySession
{
    public function __construct(
        private ?int $id,
        private EntityId $uuid,
        private UserId $userId,
        private ?int $catalogueId,
        private ?EntityId $catalogueUuid,
        private SavedListType $catalogueType,
        private FlashcardField $prompt,
        private FlashcardField $answer,
        private AnswerMode $mode,
        private ScriptStrictness $script,
        private int $cardCount,
        private ?int $correctCount,
        private DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $completedAt,
    ) {
    }

    public function getIdValue(): int
    {
        return (int) $this->id;
    }

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getCatalogueId(): ?int
    {
        return $this->catalogueId;
    }

    public function getCatalogueUuid(): ?EntityId
    {
        return $this->catalogueUuid;
    }

    public function getCatalogueType(): SavedListType
    {
        return $this->catalogueType;
    }

    public function getPrompt(): FlashcardField
    {
        return $this->prompt;
    }

    public function getAnswer(): FlashcardField
    {
        return $this->answer;
    }

    public function getMode(): AnswerMode
    {
        return $this->mode;
    }

    public function getScript(): ScriptStrictness
    {
        return $this->script;
    }

    public function getCardCount(): int
    {
        return $this->cardCount;
    }

    public function getCorrectCount(): ?int
    {
        return $this->correctCount;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function isOwnedBy(UserId $userId): bool
    {
        return $this->userId->equals($userId);
    }
}
