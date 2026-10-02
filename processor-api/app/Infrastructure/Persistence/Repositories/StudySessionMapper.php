<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\Models\StudySession as DomainStudySession;
use App\Infrastructure\Persistence\Models\StudySession as PersistenceStudySession;

class StudySessionMapper
{
    public function mapToDomain(PersistenceStudySession $entity): DomainStudySession
    {
        $catalogueUuid = $entity->relationLoaded('catalogue') ? $entity->catalogue?->uuid : null;

        return new DomainStudySession(
            id: (int) $entity->id,
            uuid: new EntityId((string) $entity->uuid),
            userId: new UserId((int) $entity->user_id),
            catalogueId: $entity->catalogue_id === null ? null : (int) $entity->catalogue_id,
            catalogueUuid: $catalogueUuid === null ? null : new EntityId((string) $catalogueUuid),
            catalogueType: SavedListType::from((int) $entity->catalogue_type),
            prompt: FlashcardField::from((string) $entity->prompt_field),
            answer: FlashcardField::from((string) $entity->answer_field),
            mode: AnswerMode::from((string) $entity->answer_mode),
            script: ScriptStrictness::from((string) $entity->script_strictness),
            cardCount: (int) $entity->card_count,
            correctCount: $entity->correct_count === null ? null : (int) $entity->correct_count,
            startedAt: $entity->started_at->toDateTimeImmutable(),
            completedAt: $entity->completed_at?->toDateTimeImmutable(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function mapToEntity(DomainStudySession $session): array
    {
        return [
            'uuid' => $session->getUuid()->value(),
            'user_id' => $session->getUserId()->value(),
            'catalogue_id' => $session->getCatalogueId(),
            'catalogue_type' => $session->getCatalogueType()->value,
            'prompt_field' => $session->getPrompt()->value,
            'answer_field' => $session->getAnswer()->value,
            'answer_mode' => $session->getMode()->value,
            'script_strictness' => $session->getScript()->value,
            'card_count' => $session->getCardCount(),
            'correct_count' => $session->getCorrectCount(),
            'started_at' => $session->getStartedAt(),
            'completed_at' => $session->getCompletedAt(),
        ];
    }
}
