<?php

declare(strict_types=1);

namespace App\Domain\Operations\DTOs;

/**
 * The latest completed dictionary import for one logical environment, as stored in
 * environment_bootstrap_runs. The environment name is the one the pipeline passed, which need
 * not match APP_ENV.
 */
final readonly class ImportRunDTO
{
    /**
     * @param array<string, int> $datasets rows written per table
     */
    public function __construct(
        public string $environment,
        public ?string $completedAt,
        public array $datasets,
    ) {
    }

    public function totalRows(): int
    {
        return array_sum($this->datasets);
    }
}
