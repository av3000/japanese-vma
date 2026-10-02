<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\Enums;

enum ImportRunStatus: string
{
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
