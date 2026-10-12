<?php

declare(strict_types=1);

namespace App\Domain\Activity\Enums;

/**
 * Which surface a privileged mutation came through.
 */
enum ActivitySource: string
{
    case Panel = 'panel';
    case Api = 'api';
    case Console = 'cli';

    public function label(): string
    {
        return match ($this) {
            self::Panel => 'Admin panel',
            self::Api => 'API',
            self::Console => 'Console',
        };
    }
}
