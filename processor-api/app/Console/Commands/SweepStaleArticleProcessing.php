<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\Processing\ProcessingThresholds;
use Illuminate\Console\Command;

class SweepStaleArticleProcessing extends Command
{
    public const DEFAULT_OLDER_THAN_SECONDS = ProcessingThresholds::STALE_AFTER_SECONDS;

    protected $signature = 'article-processing:sweep-stale
        {--older-than='.self::DEFAULT_OLDER_THAN_SECONDS.' : Mark pending/processing rows not updated for this many seconds as failed}';

    protected $description = 'Mark article processing operations that stopped reporting progress as failed so clients never wait forever';

    public function handle(ProcessingStateServiceInterface $states): int
    {
        $olderThan = max(1, (int) $this->option('older-than'));

        $swept = $states->sweepStale($olderThan);

        $this->info("Swept {$swept} stale processing operation(s) older than {$olderThan} seconds.");

        return self::SUCCESS;
    }
}
