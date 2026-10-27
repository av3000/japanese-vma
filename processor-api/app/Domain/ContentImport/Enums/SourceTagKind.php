<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\Enums;

/**
 * Which of a source's labels a tag mapping reads: its broad genres or its specific topics.
 */
enum SourceTagKind: string
{
    case Genre = 'genre';
    case Topic = 'topic';
}
