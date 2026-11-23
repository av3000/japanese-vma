<?php

declare(strict_types=1);

namespace App\Domain\Study\DTOs;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Models\Flashcard;

/**
 * Everything a catalogue can contribute to a deck for one question: the family its items
 * belong to, every card that has an answer, and every item id (eligible or not), which the
 * dictionary pool must exclude so a learner never meets their own catalogue as a wrong answer.
 */
final readonly class EligibleCardsDTO
{
    /**
     * @param list<Flashcard> $cards
     * @param int[] $catalogueItemIds
     */
    public function __construct(
        public SavedListType $baseType,
        public array $cards,
        public array $catalogueItemIds,
    ) {
    }
}
