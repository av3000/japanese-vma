<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations\HealthChecks;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Application\Operations\Services\HealthCheckRunner;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * On PostgreSQL the check opens its own `pgsql_health` connection, whose connect timeout keeps an
 * unreachable host inside the budget, and bounds the query with a transaction-local statement
 * timeout. The connection is purged afterwards, so no extra idle connection is kept. Other
 * drivers (local tooling only) query the default connection.
 */
final class DatabaseHealthCheck implements HealthCheckInterface
{
    public const CONNECTION = 'pgsql_health';

    public function name(): string
    {
        return 'Database';
    }

    public function check(): HealthCheckResultDTO
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $this->result($this->answers(DB::connection()));
        }

        if (config('database.connections.'.self::CONNECTION) === null) {
            return new HealthCheckResultDTO($this->name(), HealthStatus::NotConfigured, 'No database health connection is configured');
        }

        try {
            $connection = DB::connection(self::CONNECTION);

            return $this->result($connection->transaction(function () use ($connection): bool {
                $connection->statement('SET LOCAL statement_timeout = '.HealthCheckRunner::BUDGET_MS);

                return $this->answers($connection);
            }));
        } finally {
            DB::purge(self::CONNECTION);
        }
    }

    private function answers(ConnectionInterface $connection): bool
    {
        return (int) $connection->selectOne('SELECT 1 AS ok')->ok === 1;
    }

    private function result(bool $answered): HealthCheckResultDTO
    {
        return new HealthCheckResultDTO(
            $this->name(),
            $answered ? HealthStatus::Ok : HealthStatus::Failed,
            $answered ? 'Answered a query' : 'Unexpected answer to SELECT 1',
        );
    }
}
