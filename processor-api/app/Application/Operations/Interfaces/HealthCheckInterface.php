<?php

declare(strict_types=1);

namespace App\Application\Operations\Interfaces;

use App\Domain\Operations\DTOs\HealthCheckResultDTO;

/**
 * One dependency the application needs. Implementations may throw; the runner turns any exception
 * into a failed result, so a dependency that is down never breaks the caller.
 */
interface HealthCheckInterface
{
    public function name(): string;

    public function check(): HealthCheckResultDTO;
}
