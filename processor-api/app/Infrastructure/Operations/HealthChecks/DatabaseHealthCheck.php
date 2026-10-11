<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations\HealthChecks;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Application\Operations\Services\HealthCheckRunner;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;
use Illuminate\Support\Facades\DB;

final class DatabaseHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'Database';
    }

    public function check(): HealthCheckResultDTO
    {
        // Bound the round trip on PostgreSQL; the statement timeout applies to this transaction only.
        $answered = DB::transaction(static function (): bool {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET LOCAL statement_timeout = '.HealthCheckRunner::BUDGET_MS);
            }

            return (int) DB::selectOne('SELECT 1 AS ok')->ok === 1;
        });

        return new HealthCheckResultDTO(
            $this->name(),
            $answered ? HealthStatus::Ok : HealthStatus::Failed,
            $answered ? 'Answered a query' : 'Unexpected answer to SELECT 1',
        );
    }
}
