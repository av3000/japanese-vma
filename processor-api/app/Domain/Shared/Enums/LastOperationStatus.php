<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum LastOperationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
