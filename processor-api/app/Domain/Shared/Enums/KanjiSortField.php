<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum KanjiSortField: string
{
    case STROKE_COUNT = 'stroke_count';
    case FREQUENCY = 'frequency';
    case GRADE = 'grade';
    case JLPT = 'jlpt';
}
