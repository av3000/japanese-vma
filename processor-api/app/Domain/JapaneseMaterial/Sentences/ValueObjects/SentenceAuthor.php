<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\ValueObjects;

use App\Domain\Shared\ValueObjects\EntityId;

/**
 * The member who wrote a sentence. Imported Tatoeba sentences have none.
 */
final readonly class SentenceAuthor
{
    public function __construct(
        public string $name,
        public EntityId $uuid,
    ) {
    }
}
