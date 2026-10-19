<?php

declare(strict_types=1);

namespace App\Application\Engagement\Interfaces\Readers;

use App\Domain\Shared\DTOs\EntityReferenceDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Resolves (type, numeric id) pointers - comment parents, catalogue items - to labels in bulk.
 * Implementations run at most one query per entity type present, whatever the number of
 * pointers, and report a deleted entity as missing rather than failing.
 */
interface EntityReferenceReaderInterface
{
    /**
     * @param list<array{0: ObjectTemplateType, 1: int}> $references (type, numeric id) pairs
     *
     * @return array<string, EntityReferenceDTO> keyed by EntityReferenceDTO::key()
     */
    public function find(array $references): array;
}
