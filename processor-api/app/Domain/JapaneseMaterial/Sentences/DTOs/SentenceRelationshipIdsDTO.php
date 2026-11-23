<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\DTOs;

final readonly class SentenceRelationshipIdsDTO
{
    /**
     * @param array<int, int> $kanjiIds
     * @param array<int, int> $wordIds
     */
    public function __construct(
        public array $kanjiIds,
        public array $wordIds,
    ) {
    }
}
