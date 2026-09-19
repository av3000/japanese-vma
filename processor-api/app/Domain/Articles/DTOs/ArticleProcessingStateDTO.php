<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\LastOperationStatus;
use DateTimeImmutable;

/**
 * The current processing state of one entity for one task: a plain snapshot of a
 * processing_states row, so nothing above the persistence layer touches Eloquent.
 *
 * Name kept from the pre-ADR-0001 shape so the HTTP resources need no change; P4-1 renames the
 * vocabulary in one sweep.
 */
final readonly class ArticleProcessingStateDTO
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public int $id,
        public ProcessingEntityType $entityType,
        public string $entityId,
        public string $taskType,
        public LastOperationStatus $status,
        public int $attempt,
        public int $maxAttempts,
        public int $contentVersion,
        public ?array $metadata,
        public ?string $errorCode,
        public ?string $errorMessage,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $finishedAt,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }

    public function task(): ProcessingTaskType
    {
        return ProcessingTaskType::from($this->taskType);
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
