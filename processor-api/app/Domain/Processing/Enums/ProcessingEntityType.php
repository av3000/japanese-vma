<?php

declare(strict_types=1);

namespace App\Domain\Processing\Enums;

/**
 * Which kind of entity a processing_states row belongs to. Stored as the string value; there is
 * no Eloquent morph map on purpose (ADR 0001, point 5).
 */
enum ProcessingEntityType: string
{
    case Article = 'article';
}
