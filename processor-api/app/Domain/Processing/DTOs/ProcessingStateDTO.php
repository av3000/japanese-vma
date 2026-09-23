<?php

declare(strict_types=1);

namespace App\Domain\Processing\DTOs;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use DateTimeImmutable;

/**
 * The current processing state of one entity for one task: a plain snapshot of a
 * processing_states row, so nothing above the persistence layer touches Eloquent.
 */
final readonly class ProcessingStateDTO
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public int $id,
        public ProcessingEntityType $entityType,
        public string $entityId,
        public string $taskType,
        public ProcessingStatus $status,
        public int $attempt,
        public int $sequence,
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
