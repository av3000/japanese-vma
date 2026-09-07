<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

use App\Domain\Shared\Enums\LastOperationStatus;
use DateTimeImmutable;

/**
 * Background-processing state for one Article, flattened off the Eloquent
 * LastOperationState model so application results carry no persistence type.
 */
final readonly class ArticleProcessingStateDTO
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public int $id,
        public string $taskType,
        public LastOperationStatus $status,
        public ?array $metadata,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }
}
