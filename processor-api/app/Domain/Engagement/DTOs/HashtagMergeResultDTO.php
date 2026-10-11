<?php

declare(strict_types=1);

namespace App\Domain\Engagement\DTOs;

/**
 * What a merge did, in enough detail to undo it by hand: every link that moved, and which of
 * those were retired because the target already tagged the same entity.
 */
final readonly class HashtagMergeResultDTO
{
    /**
     * @param list<int> $movedLinkIds hashtag_entity ids now pointing at the target
     * @param list<int> $retiredLinkIds subset that was soft-deleted as a duplicate of a target link
     */
    public function __construct(
        public HashtagTagDTO $source,
        public HashtagTagDTO $target,
        public array $movedLinkIds,
        public array $retiredLinkIds,
    ) {
    }

    public function activeLinksMoved(): int
    {
        return count($this->movedLinkIds) - count($this->retiredLinkIds);
    }
}
