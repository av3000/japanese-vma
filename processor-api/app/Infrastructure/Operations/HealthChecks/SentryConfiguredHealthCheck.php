<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations\HealthChecks;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;

/**
 * Whether errors are reported anywhere. The DSN is a secret and is only ever reported as set.
 */
final class SentryConfiguredHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'Sentry (error reporting)';
    }

    public function check(): HealthCheckResultDTO
    {
        return filled(config('sentry.dsn'))
            ? new HealthCheckResultDTO($this->name(), HealthStatus::Ok, 'DSN is set')
            : new HealthCheckResultDTO($this->name(), HealthStatus::NotConfigured, 'DSN is missing');
    }
}
