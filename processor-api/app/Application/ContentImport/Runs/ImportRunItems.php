<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Runs;

use App\Domain\ContentImport\DTOs\ImportItemResult;
use App\Domain\ContentImport\Enums\ImportItemOutcome;

/**
 * What an Import Run has done so far, one item per listed article. The run's work adds to it as
 * it goes, so the items handled before a failure are still recorded when the run fails.
 */
final class ImportRunItems
{
    /** @var list<ImportItemResult> */
    private array $items = [];

    private int $created = 0;

    public function add(ImportItemResult $item): void
    {
        $this->items[] = $item;

        if ($item->outcome === ImportItemOutcome::Created || $item->outcome === ImportItemOutcome::WouldCreate) {
            $this->created++;
        }
    }

    public function listed(): int
    {
        return count($this->items);
    }

    /**
     * Created articles, or would-be creations in a dry run.
     */
    public function created(): int
    {
        return $this->created;
    }

    /**
     * @return list<ImportItemResult>
     */
    public function all(): array
    {
        return $this->items;
    }
}
