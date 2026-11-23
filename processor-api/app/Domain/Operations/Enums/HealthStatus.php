<?php

declare(strict_types=1);

namespace App\Domain\Operations\Enums;

enum HealthStatus: string
{
    case Ok = 'ok';
    case Failed = 'failed';
    case NotConfigured = 'not_configured';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Failed => 'Failed',
            self::NotConfigured => 'Not configured',
        };
    }
}
