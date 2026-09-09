<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\LastOperations\Services\LastOperationServiceInterface;
use App\Domain\Articles\DTOs\ArticleProcessingStateDTO;
use App\Infrastructure\Persistence\Models\LastOperationState;
use DateTimeImmutable;

final readonly class DatabaseArticleProcessingStateReader implements ArticleProcessingStateReaderInterface
{
    private const TASK_TYPE = 'kanji_extraction';

    public function __construct(
        private LastOperationServiceInterface $lastOperationService,
    ) {
    }

    public function latestKanjiExtractionStates(array $articleUuids): array
    {
        if ($articleUuids === []) {
            return [];
        }

        /** @var array<string, LastOperationState> $states */
        $states = $this->lastOperationService->getBatchLatestStates($articleUuids, self::TASK_TYPE);

        return array_map(
            static fn (LastOperationState $state): ArticleProcessingStateDTO => new ArticleProcessingStateDTO(
                id: (int) $state->id,
                taskType: (string) $state->task_type,
                status: $state->status,
                metadata: $state->metadata,
                createdAt: $state->created_at ? DateTimeImmutable::createFromInterface($state->created_at) : null,
                updatedAt: $state->updated_at ? DateTimeImmutable::createFromInterface($state->updated_at) : null,
            ),
            $states,
        );
    }
}
