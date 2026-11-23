<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations\HealthChecks;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;

/**
 * Configuration only, not a connection: live processing updates degrade gracefully without
 * Reverb, so it is not worth a socket round trip per page view.
 */
final class ReverbConfiguredHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'Reverb (broadcasting)';
    }

    public function check(): HealthCheckResultDTO
    {
        $missing = array_keys(array_filter([
            'app id' => config('broadcasting.connections.reverb.app_id'),
            'key' => config('broadcasting.connections.reverb.key'),
            'secret' => config('broadcasting.connections.reverb.secret'),
            'host' => config('broadcasting.connections.reverb.options.host'),
        ], static fn (mixed $value): bool => blank($value)));

        if ($missing === []) {
            return new HealthCheckResultDTO($this->name(), HealthStatus::Ok, 'Configured');
        }

        return new HealthCheckResultDTO($this->name(), HealthStatus::NotConfigured, 'Missing: '.implode(', ', $missing));
    }
}
