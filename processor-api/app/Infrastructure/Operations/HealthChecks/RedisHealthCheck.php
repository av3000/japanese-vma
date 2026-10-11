<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations\HealthChecks;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;
use Illuminate\Support\Facades\Redis;

/**
 * One PING per page view. Production Redis is Upstash, which bills per command, so the health
 * page never refreshes itself.
 */
final class RedisHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'Redis';
    }

    public function check(): HealthCheckResultDTO
    {
        if (config('database.redis.default') === null) {
            return new HealthCheckResultDTO($this->name(), HealthStatus::NotConfigured, 'No default Redis connection is configured');
        }

        $reply = Redis::connection()->ping();
        $ok = $reply === true || (is_string($reply) && str_contains(strtoupper($reply), 'PONG'));

        return new HealthCheckResultDTO(
            $this->name(),
            $ok ? HealthStatus::Ok : HealthStatus::Failed,
            $ok ? 'Answered PING' : 'Unexpected reply to PING',
        );
    }
}
