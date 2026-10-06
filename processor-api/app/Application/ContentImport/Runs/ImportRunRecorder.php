<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Runs;

use App\Application\ContentImport\Interfaces\Repositories\ImportRunRepositoryInterface;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ImportRunResult;
use App\Domain\ContentImport\Enums\ImportRunStatus;
use App\Domain\ContentImport\Errors\ContentImportErrors;
use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;
use App\Shared\Results\Result;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Everything around an Import Run's work, so the work itself only lists, filters, tags and
 * creates:
 *
 * - one run per Content Source at a time, enforced by a lock the run itself takes, so it holds
 *   however the run was started (schedule, command line, anything added later);
 * - a `content_import_runs` row opened before the work and closed after it, whatever the work
 *   does, including throwing;
 * - rows left `running` by a process that died, closed as failed;
 * - a warning when several successful runs in a row created nothing.
 *
 * A dry run writes nothing and takes no lock: it creates no articles, so it cannot race a real run.
 */
class ImportRunRecorder
{
    /**
     * The longest an Import Run is expected to take. The lock expires after this, so a process
     * that dies holding it blocks the source for at most this long.
     */
    public const MAX_RUN_SECONDS = 7200;

    public function __construct(
        private readonly ImportRunRepositoryInterface $runs,
    ) {
    }

    public static function lockName(string $sourceKey): string
    {
        return "content-import:{$sourceKey}";
    }

    /**
     * @param Closure(ImportRunItems): void $work lists, filters, tags and creates, adding each item as it goes
     *
     * @return Result Success data: ImportRunResult, whose status is `failed` when the work threw.
     *                Failure: ContentImport.RunInProgress when another run of the source holds the lock.
     */
    public function record(ContentSourceDTO $source, bool $dryRun, int $stalledAfterRuns, Closure $work): Result
    {
        if ($dryRun) {
            return Result::success($this->perform($source, true, $work));
        }

        $lock = Cache::store(config('content_import.lock_store'))->lock(self::lockName($source->key), self::MAX_RUN_SECONDS);

        if (! $lock->get()) {
            return Result::failure(ContentImportErrors::runInProgress($source->key));
        }

        try {
            $this->closeAbandonedRuns($source);
            $runId = $this->runs->start($source->id);
            $result = $this->perform($source, false, $work);
            $this->runs->finish($runId, $result);
        } finally {
            $lock->release();
        }

        return Result::success(new ImportRunResult(
            $result->sourceKey,
            $result->status,
            $result->dryRun,
            $result->items,
            $result->error,
            $this->hasStalled($source, $stalledAfterRuns),
        ));
    }

    /**
     * Runs the work and turns whatever it throws into a failed run, keeping the items it handled.
     *
     * @param Closure(ImportRunItems): void $work
     */
    private function perform(ContentSourceDTO $source, bool $dryRun, Closure $work): ImportRunResult
    {
        $items = new ImportRunItems();
        $status = ImportRunStatus::Succeeded;
        $error = null;

        try {
            $work($items);
        } catch (ContentSourceUnavailableException $e) {
            // The source as a whole could not be read: an expected way for a run to fail.
            $status = ImportRunStatus::Failed;
            $error = $e->getMessage();

            Log::warning('Content import run failed', ['source' => $source->key, 'error' => $error]);
        } catch (Throwable $e) {
            // Anything else is a bug, but the run must still end as failed, not stay `running`.
            $status = ImportRunStatus::Failed;
            $error = $e::class.': '.$e->getMessage();

            Log::error('Content import run crashed', ['source' => $source->key, 'exception' => $e]);
        }

        return new ImportRunResult($source->key, $status, $dryRun, $items->all(), $error);
    }

    /**
     * While this run holds the source's lock no other run of it can be live, so any row still
     * `running` belongs to a process that died (a deploy, running out of memory, a timeout).
     */
    private function closeAbandonedRuns(ContentSourceDTO $source): void
    {
        $closed = $this->runs->closeAbandoned($source->id);

        if ($closed > 0) {
            Log::warning('Content import closed runs that never finished', [
                'source' => $source->key,
                'runs' => $closed,
            ]);
        }
    }

    /**
     * An unofficial source rarely breaks with an error; it breaks by quietly yielding nothing.
     * Several successful runs in a row that created nothing are reported as a warning.
     */
    private function hasStalled(ContentSourceDTO $source, int $runs): bool
    {
        if ($runs < 1) {
            return false;
        }

        $counts = $this->runs->recentCreatedCounts($source->id, $runs);

        if (count($counts) < $runs || array_sum($counts) > 0) {
            return false;
        }

        Log::warning('Content import has created nothing for several runs', [
            'source' => $source->key,
            'runs' => $runs,
        ]);

        return true;
    }
}
