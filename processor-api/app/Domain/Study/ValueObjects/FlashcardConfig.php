<?php

declare(strict_types=1);

namespace App\Domain\Study\ValueObjects;

use InvalidArgumentException;

/**
 * How a deck is cut from a catalogue for one request: the question asked, how many cards,
 * and the seed that fixes the shuffle and the option order. The same seed reproduces the
 * same deck, which is what lets a reload resume a run.
 */
final readonly class FlashcardConfig
{
    public const MIN_COUNT = 1;

    public const DEFAULT_COUNT = 20;

    public const MAX_COUNT = 100;

    /** Highest value `mt_srand` and the client PRNG both represent exactly. */
    public const MAX_SEED = 2147483647;

    public function __construct(
        public FlashcardQuestion $question,
        public int $count,
        public int $seed,
    ) {
        if ($this->count < self::MIN_COUNT || $this->count > self::MAX_COUNT) {
            throw new InvalidArgumentException('Count must be between '.self::MIN_COUNT.' and '.self::MAX_COUNT);
        }

        if ($this->seed < 0 || $this->seed > self::MAX_SEED) {
            throw new InvalidArgumentException('Seed must be between 0 and '.self::MAX_SEED);
        }
    }
}
