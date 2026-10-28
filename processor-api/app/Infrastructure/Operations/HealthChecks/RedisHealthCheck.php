<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations\HealthChecks;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;
use Illuminate\Support\Facades\Redis;

/**
 * One PING per page view. Production Redis is Upstash, which bills per command, so the health
 * page never refreshes itself. The PING runs on the `health` connection, whose connect and read
 * timeouts keep an unreachable host inside the check's budget.
 */
final class RedisHealthCheck implements HealthCheckInterface
{
    public const CONNECTION = 'health';

    public function name(): string
    {
        return 'Redis';
    }

    public function check(): HealthCheckResultDTO
    {
        if (config('database.redis.default') === null || config('database.redis.'.self::CONNECTION) === null) {
            return new HealthCheckResultDTO($this->name(), HealthStatus::NotConfigured, 'No Redis health connection is configured');
        }

        $reply = Redis::connection(self::CONNECTION)->ping();
        $ok = $reply === true || (is_string($reply) && str_contains(strtoupper($reply), 'PONG'));

        return new HealthCheckResultDTO(
            $this->name(),
            $ok ? HealthStatus::Ok : HealthStatus::Failed,
            $ok ? 'Answered PING' : 'Unexpected reply to PING',
        );
    }
}
