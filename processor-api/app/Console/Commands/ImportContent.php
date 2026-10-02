<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\ContentImport\Services\ContentImportServiceInterface;
use App\Domain\ContentImport\DTOs\ImportItemResult;
use App\Domain\ContentImport\DTOs\ImportRunResult;
use App\Domain\ContentImport\Enums\ImportRunStatus;
use Illuminate\Console\Command;

/**
 * Runs the Content Import (epic #404). Scheduled daily; also the way to try a source by hand:
 * `php artisan content:import --source=nhk-news --dry-run` lists and filters without writing.
 */
class ImportContent extends Command
{
    protected $signature = 'content:import
        {--source= : Content source key; every enabled source when omitted}
        {--dry-run : List, filter and tag, but create nothing and record no run}';

    protected $description = 'Import new articles from the registered content sources as Imported Articles';

    public function handle(ContentImportServiceInterface $imports): int
    {
        $source = $this->option('source');
        $keys = is_string($source) && $source !== '' ? [$source] : $imports->enabledSourceKeys();
        $dryRun = (bool) $this->option('dry-run');
        $exitCode = self::SUCCESS;

        if ($keys === []) {
            $this->warn('No enabled content source.');

            return self::SUCCESS;
        }

        foreach ($keys as $key) {
            $result = $imports->run($key, $dryRun);

            if ($result->isFailure()) {
                $this->error("{$key}: {$result->getError()->detail}");
                $exitCode = self::FAILURE;

                continue;
            }

            /** @var ImportRunResult $run */
            $run = $result->getData();
            $this->report($run);

            if ($run->status === ImportRunStatus::Failed) {
                $exitCode = self::FAILURE;
            }
        }

        return $exitCode;
    }

    private function report(ImportRunResult $run): void
    {
        if ($this->output->isVerbose() || $run->dryRun) {
            $this->table(
                ['External id', 'Outcome', 'Tags', 'Title / detail'],
                array_map(fn (ImportItemResult $item): array => [
                    $item->externalId,
                    $item->outcome->value,
                    implode(' ', $item->tags),
                    mb_strimwidth($item->detail ?? $item->title, 0, 60, '…'),
                ], $run->items),
            );
        }

        $verb = $run->dryRun ? 'would create' : 'created';
        $line = "{$run->sourceKey}: listed {$run->listed()}, {$verb} {$run->created()}, skipped {$run->skipped()}, failed {$run->failed()}";

        if ($run->status === ImportRunStatus::Failed) {
            $this->error("{$line}. Run failed: {$run->error}");
        } else {
            $this->info($line.'.');
        }

        if ($run->stalled) {
            $this->warn("{$run->sourceKey}: several runs in a row created nothing; the source may have changed.");
        }
    }
}
