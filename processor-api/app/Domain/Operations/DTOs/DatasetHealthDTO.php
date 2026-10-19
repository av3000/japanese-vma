<?php

declare(strict_types=1);

namespace App\Domain\Operations\DTOs;

use App\Domain\Operations\Enums\DatasetDrift;

/**
 * One manifest dataset: whether its dump is on disk, how many rows the import recorded, how many
 * are there now, and whether the id sequence has fallen behind the data.
 */
final readonly class DatasetHealthDTO
{
    public function __construct(
        public string $table,
        public string $source,
        public bool $sourcePresent,
        public ?int $recordedRows,
        public ?int $liveRows,
        public DatasetDrift $drift,
        public ?bool $sequenceBehind,
    ) {
    }
}
