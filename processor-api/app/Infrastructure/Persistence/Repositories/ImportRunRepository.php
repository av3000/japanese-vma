<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\ContentImport\Interfaces\Repositories\ImportRunRepositoryInterface;
use App\Domain\ContentImport\DTOs\ImportRunResult;
use App\Domain\ContentImport\Enums\ImportRunStatus;
use App\Infrastructure\Persistence\Models\ContentImportRun;

class ImportRunRepository implements ImportRunRepositoryInterface
{
    public function start(int $contentSourceId): int
    {
        return ContentImportRun::query()->create([
            'content_source_id' => $contentSourceId,
            'status' => ImportRunStatus::Running,
            'started_at' => now(),
        ])->id;
    }

    public function finish(int $runId, ImportRunResult $result): void
    {
        ContentImportRun::query()->whereKey($runId)->update([
            'status' => $result->status->value,
            'finished_at' => now(),
            'listed' => $result->listed(),
            'created' => $result->created(),
            'skipped' => $result->skipped(),
            'failed' => $result->failed(),
            'error' => $result->error,
        ]);
    }

    public function closeAbandoned(int $contentSourceId): int
    {
        return ContentImportRun::query()
            ->where('content_source_id', $contentSourceId)
            ->where('status', ImportRunStatus::Running->value)
            ->update([
                'status' => ImportRunStatus::Failed->value,
                'finished_at' => now(),
                'error' => 'Abandoned: the run never finished',
            ]);
    }

    public function recentCreatedCounts(int $contentSourceId, int $limit): array
    {
        return ContentImportRun::query()
            ->where('content_source_id', $contentSourceId)
            ->where('status', ImportRunStatus::Succeeded->value)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('created')
            ->map(fn ($created): int => (int) $created)
            ->all();
    }
}
