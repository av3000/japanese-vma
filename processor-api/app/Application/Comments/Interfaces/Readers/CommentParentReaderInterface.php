<?php

declare(strict_types=1);

namespace App\Application\Comments\Interfaces\Readers;

use App\Domain\Comments\DTOs\CommentParentDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Resolves the parents of a page of comments. Implementations run at most one query per entity
 * type present, whatever the page size, and report a deleted parent as missing rather than
 * failing.
 */
interface CommentParentReaderInterface
{
    /**
     * @param list<array{0: ObjectTemplateType, 1: int}> $parents (type, numeric id) pairs, as stored
     *                                                            in template_id and real_object_id
     *
     * @return array<string, CommentParentDTO> keyed by CommentParentDTO::key()
     */
    public function findParents(array $parents): array;
}
