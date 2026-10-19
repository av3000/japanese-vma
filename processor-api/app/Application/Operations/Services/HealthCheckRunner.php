<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;
use Throwable;

/**
 * Runs the registered dependency checks. Shared by the admin panel's health page and, once #232
 * lands, the deep health endpoint, so both report the same checks the same way.
 *
 * Each check has a budget: checks bound their own I/O (statement or socket timeouts), and a check
 * that still overruns is reported as failed rather than left looking healthy.
 */
final class HealthCheckRunner
{
    public const BUDGET_MS = 2000;

    /**
     * @param iterable<HealthCheckInterface> $checks
     */
    public function __construct(
        private readonly iterable $checks,
    ) {
    }

    /**
     * @return list<HealthCheckResultDTO>
     */
    public function runAll(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            $results[] = $this->run($check);
        }

        return $results;
    }

    private function run(HealthCheckInterface $check): HealthCheckResultDTO
    {
        $started = hrtime(true);

        try {
            $result = $check->check();
        } catch (Throwable $exception) {
            $result = new HealthCheckResultDTO(
                $check->name(),
                HealthStatus::Failed,
                class_basename($exception).': '.mb_strimwidth($exception->getMessage(), 0, 160, '…'),
            );
        }

        $durationMs = (int) round((hrtime(true) - $started) / 1_000_000);

        if ($result->status === HealthStatus::Ok && $durationMs > self::BUDGET_MS) {
            $result = new HealthCheckResultDTO(
                $result->name,
                HealthStatus::Failed,
                "Answered, but took {$durationMs} ms (budget ".self::BUDGET_MS.' ms)',
            );
        }

        return $result->withDuration($durationMs);
    }
}
