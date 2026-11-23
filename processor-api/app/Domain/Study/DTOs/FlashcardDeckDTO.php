<?php

declare(strict_types=1);

namespace App\Domain\Study\DTOs;

use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardConfig;

/**
 * A catalogue read as a deck. Not persisted: the catalogue is the deck (epic #413).
 */
final readonly class FlashcardDeckDTO
{
    /**
     * @param list<Flashcard> $cards Shuffled and cut to the configured count.
     * @param int $totalItems Items in the catalogue.
     * @param int $eligibleItems Items with a non-empty answer field, before the cut.
     * @param int $excludedEmptyAnswerField Items skipped because the answer field is empty.
     */
    public function __construct(
        public Catalogue $catalogue,
        public FlashcardConfig $config,
        public array $cards,
        public int $totalItems,
        public int $eligibleItems,
        public int $excludedEmptyAnswerField,
    ) {
    }
}
