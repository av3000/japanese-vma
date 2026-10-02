<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces\Repositories;

use App\Domain\ContentImport\DTOs\ImportRunResult;

interface ImportRunRepositoryInterface
{
    /**
     * Open a `running` row and return its id.
     */
    public function start(int $contentSourceId): int;

    public function finish(int $runId, ImportRunResult $result): void;

    /**
     * Created counts of the most recent finished, successful runs for a source, newest first.
     *
     * @return list<int>
     */
    public function recentCreatedCounts(int $contentSourceId, int $limit): array;
}
