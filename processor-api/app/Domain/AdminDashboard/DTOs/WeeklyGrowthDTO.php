<?php

declare(strict_types=1);

namespace App\Domain\AdminDashboard\DTOs;

/**
 * Registrations and articles created per ISO week (Monday start, UTC), oldest first. Every week
 * in the window is present; a week without rows counts as zero.
 */
final readonly class WeeklyGrowthDTO
{
    /**
     * @param list<array{week_start: string, registrations: int, articles: int}> $weeks week_start is Y-m-d
     */
    public function __construct(
        public array $weeks,
    ) {
    }

    /**
     * @return list<array{week_start: string, registrations: int, articles: int}>
     */
    public function toArray(): array
    {
        return $this->weeks;
    }

    /**
     * @param list<array{week_start: string, registrations: int, articles: int}> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }
}
