<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Processing\Interfaces\Repositories\ProcessingStateRepositoryInterface;
use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;

final readonly class DatabaseArticleProcessingStateReader implements ArticleProcessingStateReaderInterface
{
    public function __construct(
        private ProcessingStateRepositoryInterface $states,
    ) {
    }

    public function currentState(string $articleUuid): ?ProcessingStateDTO
    {
        return $this->states->getCurrent(
            ProcessingEntityType::Article,
            EntityId::from($articleUuid),
            ProcessingTaskType::ArticleContentProcessing,
        );
    }

    public function currentStates(array $articleUuids): array
    {
        if ($articleUuids === []) {
            return [];
        }

        return $this->states->getCurrentBatch(
            ProcessingEntityType::Article,
            array_values($articleUuids),
            ProcessingTaskType::ArticleContentProcessing,
        );
    }
}
