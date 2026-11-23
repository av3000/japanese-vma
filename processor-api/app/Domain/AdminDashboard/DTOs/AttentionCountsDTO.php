<?php

declare(strict_types=1);

namespace App\Domain\AdminDashboard\DTOs;

/**
 * Things an administrator should look at today.
 */
final readonly class AttentionCountsDTO
{
    public function __construct(
        public int $articlesAwaitingReview,
        public int $processingFailedLastDay,
        public int $processingStuck,
        public int $failedJobs,
    ) {
    }

    /**
     * @return array{articles_awaiting_review: int, processing_failed_last_day: int, processing_stuck: int, failed_jobs: int}
     */
    public function toArray(): array
    {
        return [
            'articles_awaiting_review' => $this->articlesAwaitingReview,
            'processing_failed_last_day' => $this->processingFailedLastDay,
            'processing_stuck' => $this->processingStuck,
            'failed_jobs' => $this->failedJobs,
        ];
    }

    /**
     * @param array{articles_awaiting_review: int, processing_failed_last_day: int, processing_stuck: int, failed_jobs: int} $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            articlesAwaitingReview: $values['articles_awaiting_review'],
            processingFailedLastDay: $values['processing_failed_last_day'],
            processingStuck: $values['processing_stuck'],
            failedJobs: $values['failed_jobs'],
        );
    }
}
