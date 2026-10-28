<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Application\Operations\Services\HealthCheckRunner;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;
use App\Infrastructure\Operations\HealthChecks\DatabaseHealthCheck;
use App\Infrastructure\Operations\HealthChecks\RedisHealthCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * An unreachable dependency fails within the check's 2-second budget instead of hanging the
 * health page for the client library's default timeout (60 s for phpredis, 30 s for pdo_pgsql).
 */
final class HealthCheckTimeoutTest extends TestCase
{
    /** Not routable, so a connect attempt waits for the timeout instead of being refused. */
    private const UNREACHABLE_HOST = '10.255.255.1';

    public function test_an_unreachable_redis_fails_inside_the_budget(): void
    {
        config(['database.redis.health.host' => self::UNREACHABLE_HOST, 'database.redis.health.url' => null]);
        Redis::purge(RedisHealthCheck::CONNECTION);

        $result = $this->check(app(RedisHealthCheck::class));

        self::assertSame(HealthStatus::Failed, $result->status);
        self::assertLessThan(3000, $result->durationMs);
    }

    public function test_an_unreachable_database_fails_inside_the_budget(): void
    {
        config(['database.connections.pgsql_health.host' => self::UNREACHABLE_HOST, 'database.connections.pgsql_health.url' => null]);
        DB::purge(DatabaseHealthCheck::CONNECTION);

        $result = $this->check(app(DatabaseHealthCheck::class));

        self::assertSame(HealthStatus::Failed, $result->status);
        self::assertLessThan(3000, $result->durationMs);
    }

    public function test_both_answer_against_the_compose_services(): void
    {
        self::assertSame(HealthStatus::Ok, $this->check(app(DatabaseHealthCheck::class))->status);
        self::assertSame(HealthStatus::Ok, $this->check(app(RedisHealthCheck::class))->status);
    }

    private function check(HealthCheckInterface $check): HealthCheckResultDTO
    {
        return (new HealthCheckRunner([$check]))->runAll()[0];
    }
}
