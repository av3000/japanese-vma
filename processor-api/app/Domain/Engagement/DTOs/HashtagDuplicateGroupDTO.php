<?php

declare(strict_types=1);

namespace App\Domain\Engagement\DTOs;

/**
 * Tags that share one normalised key: case or width variants of the same tag, waiting to be
 * merged in the admin panel.
 */
final readonly class HashtagDuplicateGroupDTO
{
    /**
     * @param list<array{id: int, content: string}> $tags in id order
     */
    public function __construct(
        public string $key,
        public array $tags,
    ) {
    }
}
