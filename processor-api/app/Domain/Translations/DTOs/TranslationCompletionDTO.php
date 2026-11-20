<?php

declare(strict_types=1);

namespace App\Domain\Translations\DTOs;

/**
 * How much of the English catalogue one locale has translated.
 */
final readonly class TranslationCompletionDTO
{
    /**
     * @param int $translated English keys that have a non-empty override (orphans never count).
     * @param int $total English keys.
     * @param int $percent `translated / total` rounded DOWN to a whole number, so 1 of 4 is 25 and
     *                     2 of 3 is 66; 100 means every key is translated. 0 when there are no keys.
     */
    public function __construct(
        public int $translated,
        public int $total,
        public int $percent,
    ) {
    }
}
