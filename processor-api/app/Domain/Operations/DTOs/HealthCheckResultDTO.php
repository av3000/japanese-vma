<?php

declare(strict_types=1);

namespace App\Domain\Operations\DTOs;

use App\Domain\Operations\Enums\HealthStatus;

/**
 * One dependency check. The detail never carries a secret value: configured secrets are reported
 * as set or missing only.
 */
final readonly class HealthCheckResultDTO
{
    public function __construct(
        public string $name,
        public HealthStatus $status,
        public string $detail,
        public ?int $durationMs = null,
    ) {
    }

    public function withDuration(int $durationMs): self
    {
        return new self($this->name, $this->status, $this->detail, $durationMs);
    }
}
