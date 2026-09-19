<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\LastOperations\Services\LastOperationServiceInterface;
use Illuminate\Console\Command;

class SweepStaleArticleProcessing extends Command
{
    /**
     * Default threshold: job timeout (120 s) + queue retry_after (180 s) + margin. A row that
     * has not been touched for that long has no attempt left that could still update it.
     */
    public const DEFAULT_OLDER_THAN_SECONDS = 330;

    protected $signature = 'article-processing:sweep-stale
        {--older-than='.self::DEFAULT_OLDER_THAN_SECONDS.' : Mark pending/processing rows not updated for this many seconds as failed}';

    protected $description = 'Mark article processing operations that stopped reporting progress as failed so clients never wait forever';

    public function handle(LastOperationServiceInterface $lastOperations): int
    {
        $olderThan = max(1, (int) $this->option('older-than'));

        $swept = $lastOperations->sweepStale($olderThan);

        $this->info("Swept {$swept} stale processing operation(s) older than {$olderThan} seconds.");

        return self::SUCCESS;
    }
}
