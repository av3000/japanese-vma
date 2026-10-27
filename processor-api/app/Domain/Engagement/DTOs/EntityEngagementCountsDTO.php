<?php

declare(strict_types=1);

namespace App\Domain\Engagement\DTOs;

/**
 * How much one entity has been viewed, liked, commented on and downloaded.
 */
final readonly class EntityEngagementCountsDTO
{
    public function __construct(
        public int $views,
        public int $likes,
        public int $comments,
        public int $downloads,
    ) {
    }
}
