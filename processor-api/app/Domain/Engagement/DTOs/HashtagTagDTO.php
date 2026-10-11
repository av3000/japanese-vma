<?php

declare(strict_types=1);

namespace App\Domain\Engagement\DTOs;

/**
 * One row of `uniquehashtags`: the tag itself, not a link to an entity.
 */
final readonly class HashtagTagDTO
{
    public function __construct(
        public int $id,
        public string $content,
        public string $key,
    ) {
    }
}
