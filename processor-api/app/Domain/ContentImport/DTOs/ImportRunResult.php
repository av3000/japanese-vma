<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\DTOs;

use App\Domain\ContentImport\Enums\ImportItemOutcome;
use App\Domain\ContentImport\Enums\ImportRunStatus;

/**
 * The outcome of one Import Run against one Content Source. A dry run has the same shape; its
 * would-be creations are counted as `created` so the two read alike.
 */
final readonly class ImportRunResult
{
    /**
     * @param list<ImportItemResult> $items
     */
    public function __construct(
        public string $sourceKey,
        public ImportRunStatus $status,
        public bool $dryRun,
        public array $items,
        public ?string $error = null,
        // True when this and the runs before it created nothing for the configured number of runs.
        public bool $stalled = false,
    ) {
    }

    public function listed(): int
    {
        return count($this->items);
    }

    public function created(): int
    {
        return $this->count(fn (ImportItemOutcome $o): bool => $o === ImportItemOutcome::Created || $o === ImportItemOutcome::WouldCreate);
    }

    public function skipped(): int
    {
        return $this->count(fn (ImportItemOutcome $o): bool => $o->isSkip());
    }

    public function failed(): int
    {
        return $this->count(fn (ImportItemOutcome $o): bool => $o === ImportItemOutcome::Failed);
    }

    /**
     * @param callable(ImportItemOutcome): bool $matches
     */
    private function count(callable $matches): int
    {
        return count(array_filter($this->items, fn (ImportItemResult $item): bool => $matches($item->outcome)));
    }
}
