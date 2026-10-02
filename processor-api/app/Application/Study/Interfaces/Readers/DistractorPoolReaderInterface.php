<?php

declare(strict_types=1);

namespace App\Application\Study\Interfaces\Readers;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardConfig;

/**
 * Read port for wrong-answer candidates taken from the whole dictionary, for decks too
 * small or too homogeneous to supply their own.
 */
interface DistractorPoolReaderInterface
{
    /**
     * Random items of `$baseType` that have a value for the configured answer field, as
     * cards for `$config`, excluding `$excludeItemIds` (every item of the catalogue, not
     * only the cards in the cut deck). When `$preferJlpt` or `$preferStrokes` is given,
     * matching items come first and the rest fill up to `$limit`.
     *
     * @param int[] $excludeItemIds
     *
     * @return list<Flashcard> In random order; may be shorter than `$limit`.
     */
    public function sample(
        SavedListType $baseType,
        FlashcardConfig $config,
        array $excludeItemIds,
        ?string $preferJlpt,
        ?int $preferStrokes,
        int $limit,
    ): array;
}
